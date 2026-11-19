<?php

namespace Tests\Feature;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\BookingStatus;
use App\Enums\EncodingStatus;
use App\Enums\MaterialStatus;
use App\Enums\RecordingStatus;
use App\Enums\RoleSlug;
use App\Jobs\ProcessVideoToHlsJob;
use App\Models\Booking;
use App\Models\Module;
use App\Models\ModuleVideo;
use App\Models\Payment;
use App\Models\SessionRecording;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Certificate\CertificateService;
use App\Services\Media\SelfHostedVideoService;
use App\Services\Operations\BackupService;
use App\Services\Payment\BillplzGatewayService;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SessionAndBookingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use ZipArchive;

class Stage10ProductionAndServerStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleAndPermissionSeeder::class,
            DemoUserSeeder::class,
            CatalogueSeeder::class,
            SessionAndBookingSeeder::class,
        ]);
    }

    public function test_company_server_storage_directories_are_isolated_from_public_root_and_direct_access_is_blocked(): void
    {
        $publicRoot = str_replace('\\', '/', realpath(public_path()) ?: public_path());

        $roots = [];
        foreach (['materials', 'videos', 'recordings', 'certificates'] as $disk) {
            Storage::disk($disk)->makeDirectory('.');
            $diskRoot = (string) config("filesystems.disks.{$disk}.root");
            $normalizedRoot = str_replace('\\', '/', realpath($diskRoot) ?: $diskRoot);

            $this->assertStringStartsNotWith($publicRoot, $normalizedRoot);
            $this->assertFalse((bool) config("filesystems.disks.{$disk}.serve"));
            $this->assertSame('private', config("filesystems.disks.{$disk}.visibility"));
            $roots[$disk] = $normalizedRoot;
        }

        // All four storage categories occupy distinct subdirectories
        $this->assertCount(4, array_unique(array_values($roots)));

        // Direct HTTP requests targeting internal X-Accel paths or dotfiles never leak protected files
        $this->get('/_etms_internal_storage/materials/modules/eht-hyd-101-msma-workbook.pdf')->assertNotFound();
        $this->get('/.env')->assertNotFound();
        $this->get('/.env.production')->assertNotFound();
    }

    public function test_ffmpeg_hls_transcoding_and_authenticated_segment_streaming(): void
    {
        Storage::fake('videos');
        Storage::fake('recordings');

        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $instructor = User::query()->where('email', 'instructor@ecohydrotech.com.my')->firstOrFail();
        $otherTrainee = User::factory()->create(['email_verified_at' => now()]);
        $otherTrainee->assignRole(RoleSlug::Customer);

        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();

        // Generate a real 1-second test MP4 video via FFmpeg on fake private 'videos' disk
        Storage::disk('videos')->makeDirectory('sources');
        $sourceKey = 'sources/stage10-sample.mp4';
        $absMp4 = Storage::disk('videos')->path($sourceKey);

        $genProcess = new Process([
            (string) config('etms.video.ffmpeg_binary', 'ffmpeg'),
            '-y',
            '-f', 'lavfi', '-i', 'testsrc=duration=1:size=320x240:rate=15',
            '-f', 'lavfi', '-i', 'sine=frequency=1000:duration=1',
            '-c:v', 'libx264', '-pix_fmt', 'yuv420p',
            '-c:a', 'aac', '-shortest',
            $absMp4,
        ]);
        $genProcess->setTimeout(30);
        $genProcess->run();

        $this->assertTrue($genProcess->isSuccessful(), 'FFmpeg test sample synthesis failed: '.$genProcess->getErrorOutput());
        $this->assertTrue(Storage::disk('videos')->exists($sourceKey));

        $video = ModuleVideo::query()->create([
            'module_id' => $module->id,
            'title' => 'Stage 10 Self-Hosted FFmpeg HLS Lesson',
            'provider' => 'local_hls',
            'provider_library_id' => 'local',
            'provider_video_id' => 'vid-stg10-hls-01',
            'storage_disk' => 'videos',
            'source_storage_key' => $sourceKey,
            'encoding_status' => EncodingStatus::Pending,
            'sort_order' => 10,
            'status' => MaterialStatus::Published,
            'published_at' => now(),
            'uploaded_by' => $admin->id,
        ]);

        // Run queued FFmpeg HLS transcoding job synchronously
        ProcessVideoToHlsJob::dispatchSync(ModuleVideo::class, $video->id);

        $video->refresh();
        $this->assertSame(EncodingStatus::Ready, $video->encoding_status);
        $this->assertNotEmpty($video->hls_manifest_key);
        $this->assertTrue(Storage::disk('videos')->exists((string) $video->hls_manifest_key));
        $this->assertGreaterThanOrEqual(1, (int) $video->duration_seconds);

        $videoService = app(SelfHostedVideoService::class);
        $manifestUrl = $videoService->temporaryModuleVideoManifestUrl($video, 60);
        $this->assertNotNull($manifestUrl);

        // Unauthorized trainee is blocked from streaming manifest even with a valid signature
        $this->actingAs($otherTrainee)
            ->get($manifestUrl)
            ->assertForbidden();

        // Grant entitlement to trainee and verify manifest + rewritten signed .ts segment stream
        $module->entitlements()->create([
            'user_id' => $trainee->id,
            'grant_source' => 'admin_grant',
            'status' => 'active',
            'granted_at' => now(),
            'granted_by' => $admin->id,
        ]);

        $manifestResponse = $this->actingAs($trainee)
            ->get($manifestUrl)
            ->assertOk();

        $this->assertStringContainsString('application/vnd.apple.mpegurl', (string) $manifestResponse->headers->get('Content-Type'));
        $manifestBody = $manifestResponse->getContent();
        $this->assertStringContainsString('seg_0000.ts?expires=', $manifestBody);

        // Extract signed segment URL from rewritten playlist and verify authenticated TS stream
        preg_match('/(https?:\/\/[^\s]+seg_0000\.ts[^\s]*)/', $manifestBody, $segmentMatch);
        $this->assertNotEmpty($segmentMatch[1] ?? null);

        $segResponse = $this->actingAs($trainee)
            ->get($segmentMatch[1])
            ->assertOk();
        $this->assertSame('video/mp2t', $segResponse->headers->get('Content-Type'));

        // Expired HLS asset signature must reject with 403
        $expiredAssetUrl = URL::temporarySignedRoute(
            'portal.modules.videos.stream',
            now()->subMinute(),
            ['module' => $module->slug, 'video' => $video->id, 'asset' => 'seg_0000.ts'],
        );
        $this->actingAs($trainee)->get($expiredAssetUrl)->assertForbidden();

        // Test instructor uploading a video recording -> admin publish -> attendees_only HLS streaming
        $session = TrainingSession::query()->where('session_code', 'SES-2026-003')->firstOrFail();
        $uploadedFile = new UploadedFile($absMp4, 'wetland-field-session.mp4', 'video/mp4', null, true);

        $this->actingAs($instructor)
            ->post(route('instructor.sessions.recordings.store', $session), [
                'title' => 'Uploaded Wetland Recording Part 2',
                'video_file' => $uploadedFile,
            ])
            ->assertRedirect(route('instructor.sessions.recordings', $session));

        $recording = SessionRecording::query()
            ->where('training_session_id', $session->id)
            ->where('title', 'Uploaded Wetland Recording Part 2')
            ->firstOrFail();

        $this->assertNotEmpty($recording->hls_manifest_key);
        $this->assertTrue(Storage::disk('recordings')->exists((string) $recording->hls_manifest_key));

        // Publish recording & verify attendance gate (D-017)
        $recording->update([
            'status' => RecordingStatus::Published,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'published_at' => now(),
        ]);

        $recManifestUrl = $videoService->temporaryRecordingManifestUrl($recording, 60);
        $this->assertNotNull($recManifestUrl);

        // Pending attendance -> forbidden
        $this->actingAs($trainee)->get($recManifestUrl)->assertForbidden();

        // Mark recorded, submitted & verified present -> streams HLS manifest cleanly
        $session->attendances()->where('user_id', $trainee->id)->update([
            'attendance_status' => AttendanceStatus::Present->value,
            'recorded_by' => $instructor->id,
            'recorded_at' => now(),
            'review_status' => AttendanceReviewStatus::Verified->value,
            'submitted_by' => $instructor->id,
            'submitted_at' => now(),
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        $recManifestResp = $this->actingAs($trainee)->get($recManifestUrl)->assertOk();
        $this->assertStringContainsString('seg_0000.ts?expires=', $recManifestResp->getContent());
    }

    public function test_security_hardening_rate_limiters_and_end_to_end_critical_workflow(): void
    {
        Storage::fake('certificates');

        // 1. Security Headers & Rate Limiters
        $this->assertNotNull(RateLimiter::limiter('auth-register'));
        $this->assertNotNull(RateLimiter::limiter('password-reset'));
        $this->assertNotNull(RateLimiter::limiter('downloads'));
        $this->assertNotNull(RateLimiter::limiter('video-streams'));
        $this->assertNotNull(RateLimiter::limiter('webhooks'));

        $homeResp = $this->get('/');
        $homeResp->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // 2. End-to-End Customer Flow: Registration -> Session Booking -> Billplz Paid Webhook -> Certificate -> Report
        $regResp = $this->post(route('register.store'), [
            'name' => 'Nurul Hidayah',
            'email' => 'nurul.e2e@example.com.my',
            'phone' => '+60123456789',
            'customer_type' => 'individual',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);
        $regResp->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'nurul.e2e@example.com.my')->firstOrFail();
        $user->markEmailAsVerified();

        $session = TrainingSession::query()->where('session_code', 'SES-2026-002')->firstOrFail();

        $this->actingAs($user)
            ->post(route('checkout.session.store', $session), [
                'customer_type' => 'individual',
                'phone' => '+60123456789',
            ])
            ->assertRedirect();

        $booking = Booking::query()->where('user_id', $user->id)->where('training_session_id', $session->id)->firstOrFail();
        $this->assertSame(BookingStatus::PendingPayment, $booking->status);

        $payment = Payment::query()->where('order_id', $booking->order_id)->firstOrFail();
        $billId = $payment->gateway_bill_id ?: 'bp_e2e_'.strtolower($payment->payment_reference);
        if (! $payment->gateway_bill_id) {
            $payment->update(['gateway_bill_id' => $billId]);
        }

        // Fire signed Billplz webhook callback
        $payload = [
            'id' => $billId,
            'collection_id' => (string) config('services.billplz.collection_id'),
            'paid' => 'true',
            'state' => 'paid',
            'amount' => (string) ((int) round(((float) $payment->amount) * 100)),
            'paid_amount' => (string) ((int) round(((float) $payment->amount) * 100)),
            'paid_at' => now()->toIso8601String(),
            'transaction_id' => 'txn_e2e_10',
            'transaction_status' => 'completed',
        ];
        $payload['x_signature'] = app(BillplzGatewayService::class)->computeCallbackSignature($payload);

        $this->postJson(route('webhooks.billplz'), $payload)->assertOk();
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);

        // Issue Certificate & verify download + notification
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $certificate = app(CertificateService::class)->issueCertificate(
            admin: $admin,
            trainee: $user,
            recordType: 'session',
            recordId: $session->id,
            completionDate: now()->toDateString(),
        );

        $this->actingAs($user)
            ->get($certificate->temporaryDownloadUrl(15))
            ->assertOk();

        // Customer has in-app booking, payment, and certificate notifications
        $this->actingAs($user)
            ->get(route('portal.notifications.index'))
            ->assertOk()
            ->assertSee('Booking Confirmed')
            ->assertSee('Certificate Issued');
    }

    public function test_production_readiness_check_and_isolated_backup_retention(): void
    {
        $tempBackupRoot = storage_path('framework/testing/stage10-backups');
        File::deleteDirectory($tempBackupRoot);
        config(['etms.backup.root' => $tempBackupRoot]);

        // Place a sample file inside server materials storage to verify backup archive includes private files
        Storage::disk('materials')->put('modules/stage10-backup-sample.txt', 'backup-integrity-content');

        // Place an old expired archive to test retention pruning
        File::ensureDirectoryExists("{$tempBackupRoot}/storage");
        $staleFile = "{$tempBackupRoot}/storage/etms-storage-stale.zip";
        file_put_contents($staleFile, 'old-zip');
        touch($staleFile, now()->subDays(30)->timestamp);

        $result = app(BackupService::class)->runBackup(keepDays: 7, includeDatabase: true);

        $this->assertFileExists($result['storage_archive']);
        $this->assertFileExists((string) $result['database_archive']);
        $this->assertFileDoesNotExist($staleFile);
        $this->assertSame(1, $result['pruned_archives']);
        $this->assertGreaterThan(0, $result['storage_files_count']);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($result['storage_archive']) === true);
        $this->assertNotFalse($zip->locateName('private/materials/modules/stage10-backup-sample.txt'));
        $zip->close();

        // Non-strict readiness check succeeds against healthy Docker MySQL, Redis, FFmpeg, FFprobe & isolated disks
        $exitCode = Artisan::call('etms:production-check');
        $this->assertSame(0, $exitCode, Artisan::output());

        // Strict production check fails if debug mode or sandbox credentials remain active in test env
        $strictExit = Artisan::call('etms:production-check', ['--strict' => true]);
        $this->assertSame(1, $strictExit);

        File::deleteDirectory($tempBackupRoot);
    }
}

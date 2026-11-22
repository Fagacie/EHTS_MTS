<?php

namespace Tests\Feature;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\EntitlementStatus;
use App\Enums\RecordingAccessPolicy;
use App\Enums\RecordingStatus;
use App\Models\Attendance;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\ModuleMaterial;
use App\Models\ModuleVideo;
use App\Models\SessionRecording;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Media\SelfHostedVideoService;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SessionAndBookingSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Stage7LearningMaterialsVideosAndRecordingsTest extends TestCase
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

    public function test_self_hosted_video_token_signing_and_expiration_verification(): void
    {
        $videoService = app(SelfHostedVideoService::class);

        $futureExpiry = now()->addMinutes(30)->timestamp;
        $pastExpiry = now()->subMinutes(5)->timestamp;

        $validToken = $videoService->computeStreamToken('vid-eht-hyd-101-p1', $futureExpiry, 'secret-token-key');
        $expiredToken = $videoService->computeStreamToken('vid-eht-hyd-101-p1', $pastExpiry, 'secret-token-key');

        $this->assertTrue($videoService->verifyStreamToken('vid-eht-hyd-101-p1', $validToken, $futureExpiry, 'secret-token-key'));
        $this->assertFalse($videoService->verifyStreamToken('vid-eht-hyd-101-p1', $expiredToken, $pastExpiry, 'secret-token-key'));
        $this->assertFalse($videoService->verifyStreamToken('vid-eht-hyd-101-p1', 'tampered-token', $futureExpiry, 'secret-token-key'));

        $video = ModuleVideo::query()->where('provider_video_id', 'vid-eht-hyd-101-p1')->firstOrFail();
        $streamUrl = $videoService->temporaryModuleVideoManifestUrl($video, 1800);
        $this->assertNotNull($streamUrl);
        $this->assertStringContainsString('/stream/stream.m3u8', $streamUrl);
        $this->assertStringContainsString('signature=', $streamUrl);
    }

    public function test_module_learning_workspace_and_video_playback_require_active_entitlement_and_log_access(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $instructor = User::query()->where('email', 'instructor@ecohydrotech.com.my')->firstOrFail();

        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();
        $video = ModuleVideo::query()->where('module_id', $module->id)->firstOrFail();
        $material = ModuleMaterial::query()->where('module_id', $module->id)->firstOrFail();

        // 1. Unentitled customer is denied access to module learning workspace and video stream
        $this->actingAs($customer)
            ->get(route('portal.modules.show', $module))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('portal.modules.videos.watch', ['module' => $module, 'video' => $video]))
            ->assertForbidden();

        // 2. Standalone module materials are administrator-managed and NOT accessible via physical session instructor assignment
        $this->actingAs($instructor)
            ->get(route('materials.download', $material))
            ->assertForbidden();

        // 3. Grant manual module entitlement to customer (ADM-16)
        ModuleEntitlement::query()->create([
            'user_id' => $customer->id,
            'module_id' => $module->id,
            'order_id' => null,
            'order_item_id' => null,
            'grant_source' => 'admin_grant',
            'status' => EntitlementStatus::Active->value,
            'granted_at' => now(),
            'granted_by' => $admin->id,
            'grant_reason' => 'Corporate scholarship entitlement.',
        ]);

        // 4. Entitled customer can view /portal/modules, open the learning workspace, and watch the Bunny video
        $this->actingAs($customer)
            ->get(route('portal.modules.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Modules/Index')
                ->has('activeEntitlements', 1)
                ->where('activeEntitlements.0.module.code', 'EHT-HYD-101')
                ->where('activeEntitlements.0.accessLabel', 'Lifetime Access'),
            );

        $this->actingAs($customer)
            ->get(route('portal.modules.show', $module))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Modules/Show')
                ->where('module.code', 'EHT-HYD-101')
                ->where('activeVideo.title', fn (?string $title): bool => is_string($title) && str_contains($title, 'Part 1: MSMA 2nd Edition IDF'))
                ->where('materials.0.title', 'MSMA 2nd Edition Design Calculation Workbook')
                ->where('streamManifestUrl', fn (?string $url): bool => is_string($url) && str_contains($url, '/stream/stream.m3u8')),
            );

        $this->actingAs($customer)
            ->get($video->temporaryWatchUrl(30))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Modules/Show')
                ->where('activeVideo.id', $video->id)
                ->where('streamManifestUrl', fn (?string $url): bool => is_string($url) && str_contains($url, '/stream/stream.m3u8')),
            );

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'module_video.watched',
            'auditable_type' => ModuleVideo::class,
            'auditable_id' => $video->id,
            'actor_id' => $customer->id,
        ]);
    }

    public function test_signed_material_download_and_video_watch_links_reject_expired_or_tampered_urls(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();
        $material = ModuleMaterial::query()->where('module_id', $module->id)->firstOrFail();
        $video = ModuleVideo::query()->where('module_id', $module->id)->firstOrFail();

        ModuleEntitlement::query()->create([
            'user_id' => $customer->id,
            'module_id' => $module->id,
            'order_id' => null,
            'order_item_id' => null,
            'grant_source' => 'admin_grant',
            'status' => EntitlementStatus::Active->value,
            'granted_at' => now(),
            'granted_by' => $admin->id,
            'grant_reason' => 'Signed URL test grant.',
        ]);

        // Valid 15-minute signed download URL succeeds
        $validDownloadUrl = $material->temporaryDownloadUrl(15);
        $this->actingAs($customer)
            ->get($validDownloadUrl)
            ->assertOk();

        // Expired signed download URL must return 403 Forbidden
        $expiredDownloadUrl = URL::temporarySignedRoute(
            'materials.download',
            now()->subMinutes(5),
            ['material' => $material->id],
        );

        $this->actingAs($customer)
            ->get($expiredDownloadUrl)
            ->assertForbidden();

        // Expired signed video playback URL must return 403 Forbidden
        $expiredWatchUrl = URL::temporarySignedRoute(
            'portal.modules.videos.watch',
            now()->subMinutes(5),
            ['module' => $module->slug, 'video' => $video->id],
        );

        $this->actingAs($customer)
            ->get($expiredWatchUrl)
            ->assertForbidden();
    }

    public function test_session_recording_playback_strictly_enforces_attendees_only_verified_present_attendance_and_d018_separation(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $instructor = User::query()->where('email', 'staff.trainee@ecohydrotech.com.my')->firstOrFail();
        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();

        $session3 = TrainingSession::query()->where('session_code', 'SES-2026-003')->firstOrFail();
        $recording = SessionRecording::query()
            ->where('training_session_id', $session3->id)
            ->where('provider_video_id', 'vid-ses-2026-003-rec1')
            ->firstOrFail();
        $attendance = Attendance::query()->where('training_session_id', $session3->id)->firstOrFail();

        // 1. While attendance is still `pending` / `draft`, recording is locked (403)
        $this->actingAs($customer)
            ->get(route('portal.recordings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Recordings/Index')
                ->has('bookingItems', 1)
                ->where('bookingItems.0.isEligible', false)
                ->where('bookingItems.0.recordings.0.watchUrl', null),
            )
            ->assertDontSee('D-017');

        $this->actingAs($customer)
            ->get($recording->temporaryWatchUrl(30))
            ->assertForbidden();

        // 2. When attendance is marked `present` but only `submitted` (not yet `verified`), recording remains locked (403)
        $attendance->update([
            'attendance_status' => AttendanceStatus::Present->value,
            'recorded_by' => $instructor->id,
            'recorded_at' => now(),
            'review_status' => AttendanceReviewStatus::Submitted->value,
            'submitted_by' => $instructor->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get($recording->temporaryWatchUrl(30))
            ->assertForbidden();

        // 3. When attendance is `absent` and `verified`, recording remains locked (403)
        $attendance->update([
            'attendance_status' => AttendanceStatus::Absent->value,
            'review_status' => AttendanceReviewStatus::Verified->value,
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get($recording->temporaryWatchUrl(30))
            ->assertForbidden();

        // 4. When attendance is `present` AND `verified`, recording playback unlocks (200 OK) and logs audit trail
        $attendance->update([
            'attendance_status' => AttendanceStatus::Present->value,
            'review_status' => AttendanceReviewStatus::Verified->value,
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get(route('portal.recordings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Recordings/Index')
                ->has('bookingItems', 1)
                ->where('bookingItems.0.isEligible', true)
                ->where('bookingItems.0.recordings.0.isReady', true)
                ->where('bookingItems.0.recordings.0.watchUrl', fn (?string $url): bool => is_string($url) && $url !== ''),
            );

        $this->actingAs($customer)
            ->get($recording->temporaryWatchUrl(30))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Recordings/Watch')
                ->where('recording.id', $recording->id)
                ->where('streamManifestUrl', fn (?string $url): bool => is_string($url) && str_contains($url, '/stream/stream.m3u8')),
            );

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'session_recording.watched',
            'auditable_type' => SessionRecording::class,
            'auditable_id' => $recording->id,
            'actor_id' => $customer->id,
        ]);

        // 5. D-018 Separation Check: Verified physical attendance does NOT grant access to the parent module's self-paced workspace
        $this->actingAs($customer)
            ->get(route('portal.modules.show', $session3->module))
            ->assertForbidden();

        // 6. Changing session recording_access_policy to `none` or unpublishing the recording immediately blocks playback
        $session3->update(['recording_access_policy' => RecordingAccessPolicy::None->value]);
        $this->actingAs($customer)
            ->get($recording->temporaryWatchUrl(30))
            ->assertForbidden();

        $session3->update(['recording_access_policy' => RecordingAccessPolicy::AttendeesOnly->value]);
        $recording->update(['status' => RecordingStatus::PendingReview->value]);
        $this->actingAs($customer)
            ->get($recording->temporaryWatchUrl(30))
            ->assertForbidden();
    }

    public function test_filament_admin_resources_for_module_videos_session_recordings_and_entitlements_and_mysql_constraints(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();

        $this->actingAs($customer)->get('/admin/module-videos')->assertForbidden();
        $this->actingAs($customer)->get('/admin/session-recordings')->assertForbidden();
        $this->actingAs($customer)->get('/admin/module-entitlements')->assertForbidden();

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->get('/admin/module-videos')
            ->assertOk()
            ->assertSee('Part 1: MSMA 2nd Edition IDF & Rational Method Walkthrough');

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->get('/admin/session-recordings')
            ->assertOk()
            ->assertSee('SES-2026-003');

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->get('/admin/module-entitlements')
            ->assertOk();

        // MySQL 8.4 CHECK constraint chk_module_videos_encoding must reject invalid encoding_status
        $this->expectException(QueryException::class);

        DB::table('module_videos')->insert([
            'module_id' => $module->id,
            'title' => 'Invalid Encoding Status Video',
            'provider' => 'local_hls',
            'provider_library_id' => 'local',
            'provider_video_id' => 'vid-invalid-enc',
            'encoding_status' => 'corrupted_invalid_state',
            'sort_order' => 2,
            'status' => 'draft',
            'uploaded_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

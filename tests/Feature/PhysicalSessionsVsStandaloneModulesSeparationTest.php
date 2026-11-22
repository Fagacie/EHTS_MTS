<?php

namespace Tests\Feature;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\BookingStatus;
use App\Enums\EncodingStatus;
use App\Enums\EntitlementStatus;
use App\Enums\MaterialStatus;
use App\Enums\ModuleStatus;
use App\Enums\OrderStatus;
use App\Enums\RecordingStatus;
use App\Enums\RoleSlug;
use App\Enums\SessionMaterialStatus;
use App\Enums\SessionStatus;
use App\Jobs\ProcessVideoToHlsJob;
use App\Models\Attendance;
use App\Models\Booking;
use App\Models\Certificate;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\ModuleMaterial;
use App\Models\ModuleVideo;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SessionMaterial;
use App\Models\SessionRecording;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Certificate\CertificateService;
use App\Services\Payment\PaymentOrchestrationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PhysicalSessionsVsStandaloneModulesSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        config()->set('services.paystack.secret_key', 'sk_test_separation_secret');
        config()->set('services.paystack.base_url', 'https://api.paystack.co');
        config()->set('etms.currency', 'NGN');
    }

    private function createUserWithRole(RoleSlug $role, string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_physical_training_session_end_to_end_lifecycle_without_creating_module_or_entitlement(): void
    {
        Storage::fake('public');
        Storage::fake('materials');
        Storage::fake('session-materials');
        Storage::fake('videos');
        Storage::fake('certificates');
        Queue::fake([ProcessVideoToHlsJob::class]);

        $admin = $this->createUserWithRole(RoleSlug::Administrator, 'admin-sep@example.com');
        $assignedInstructor = $this->createUserWithRole(RoleSlug::Instructor, 'inst-assigned@example.com');
        $otherInstructor = $this->createUserWithRole(RoleSlug::Instructor, 'inst-other@example.com');
        $trainee = $this->createUserWithRole(RoleSlug::Customer, 'trainee-phys@example.com');
        $nonBookedTrainee = $this->createUserWithRole(RoleSlug::Customer, 'trainee-unbooked@example.com');

        // 1. Admin creates and publishes a physical training session with NO module required
        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->post(route('admin-vue.sessions.store'), [
                'title' => 'Applied Floodplain Hydrology Workshop',
                'description' => 'Hands-on in-person workshop covering HEC-RAS floodplain modelling.',
                'starts_at' => now()->addDays(10)->format('Y-m-d\TH:i'),
                'ends_at' => now()->addDays(11)->format('Y-m-d\TH:i'),
                'registration_opens_at' => now()->subDay()->format('Y-m-d\TH:i'),
                'registration_closes_at' => now()->addDays(9)->format('Y-m-d\TH:i'),
                'venue_name' => 'Ecohydrotech Engineering Hall',
                'venue_address' => '12 Marina Way, Victoria Island',
                'venue_city' => 'Lagos',
                'venue_state' => 'Lagos',
                'capacity' => 20,
                'price' => 45000,
                'recording_access_policy' => 'attendees_only',
                'status' => SessionStatus::Published->value,
                'instructors' => [
                    ['instructor_id' => $assignedInstructor->id, 'role_label' => 'lead'],
                ],
            ])
            ->assertRedirect(route('admin-vue.sessions.index'));

        $session = TrainingSession::query()->where('title', 'Applied Floodplain Hydrology Workshop')->firstOrFail();

        $this->assertNull($session->module_id);
        $this->assertSame(0, Module::withTrashed()->count(), 'Creating a physical session must NOT create a compatibility module.');

        // 2. Public physical sessions list shows it; public standalone modules page does NOT
        $this->get(route('sessions.show', $session))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Sessions/Show')
                ->where('session.title', 'Applied Floodplain Hydrology Workshop')
                ->missing('module'),
            );

        $this->get(route('catalogue.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Catalogue/Index')
                ->has('modules', 0),
            );

        // Legacy session-document checkout endpoint is retired (404)
        $this->actingAs($trainee)
            ->get('/checkout/session-document/'.$session->slug)
            ->assertNotFound();

        // 3. Trainee registers and completes payment via Paystack
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/phys-session',
                    'access_code' => 'acc_phys_01',
                    'reference' => 'mock-ref',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($trainee)
            ->post(route('checkout.session.store', $session), [
                'phone' => '+2348011112222',
                'billing_address_line1' => '12 Marina Way',
                'billing_city' => 'Lagos',
                'billing_state' => 'Lagos',
                'billing_postcode' => '100001',
            ]);

        $payment = Payment::query()->latest('id')->firstOrFail();
        $booking = Booking::query()->where('user_id', $trainee->id)->where('training_session_id', $session->id)->firstOrFail();

        $response->assertRedirect(route('portal.bookings.show', $booking));

        $this->actingAs($trainee)
            ->get(route('portal.bookings.show', $booking))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Bookings/Show')
                ->where('booking.isHoldActive', true),
            );

        Http::fake([
            '*/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => $payment->gateway_bill_id,
                    'amount' => 4500000,
                    'currency' => 'NGN',
                    'id' => 987654,
                    'channel' => 'card',
                    'paid_at' => now()->toIso8601String(),
                ],
            ], 200),
        ]);

        app(PaymentOrchestrationService::class)->verifyPaystackPayment($payment);

        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(0, ModuleEntitlement::query()->count(), 'Physical session purchase must NOT create any ModuleEntitlement.');

        // 4. Unassigned instructor is denied session access & upload; assigned instructor can upload materials & recording
        $this->actingAs($otherInstructor)
            ->post(route('instructor.sessions.materials.store', $session), [
                'title' => 'Unauthorized File',
                'material_type' => 'pdf',
                'material_file' => UploadedFile::fake()->create('unauth.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->actingAs($assignedInstructor)
            ->post(route('instructor.sessions.materials.store', $session), [
                'title' => 'Floodplain Field Worksheet',
                'description' => 'Supplementary field notes for attendees.',
                'material_type' => 'pdf',
                'material_file' => UploadedFile::fake()->create('field-worksheet.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect();

        $sessionMaterial = SessionMaterial::query()->where('training_session_id', $session->id)->firstOrFail();
        $this->assertSame(SessionMaterialStatus::PendingReview, $sessionMaterial->status);

        // Pending material: assigned instructor and admin can preview/download; trainee cannot yet
        $this->actingAs($assignedInstructor)->get($sessionMaterial->temporaryDownloadUrl(15))->assertOk();
        $this->actingAs($admin)->get($sessionMaterial->temporaryDownloadUrl(15))->assertOk();
        $this->actingAs($trainee)->get($sessionMaterial->temporaryDownloadUrl(15))->assertForbidden();

        // Assigned instructor uploads session recording (pending review)
        $this->actingAs($assignedInstructor)
            ->post(route('instructor.sessions.recordings.store', $session), [
                'title' => 'Day 1 Physical Session Recording',
                'description' => 'Full live session capture.',
                'video_file' => UploadedFile::fake()->create('day1.mp4', 200, 'video/mp4'),
            ])
            ->assertRedirect();

        $recording = SessionRecording::query()->where('training_session_id', $session->id)->firstOrFail();
        $this->assertSame(RecordingStatus::PendingReview, $recording->status);
        Queue::assertPushed(ProcessVideoToHlsJob::class);

        // 5. Training is conducted & completed: instructor marks attendance & submits register -> Admin verifies attendance & publishes material + recording
        $session->update([
            'registration_opens_at' => now()->subDays(10),
            'registration_closes_at' => now()->subDays(3),
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
            'status' => SessionStatus::Completed->value,
        ]);

        $attendance = Attendance::query()->where('booking_id', $booking->id)->firstOrFail();
        $this->actingAs($assignedInstructor)
            ->put(route('instructor.sessions.attendance.update', $session), [
                'records' => [
                    $attendance->id => [
                        'attendance_status' => AttendanceStatus::Present->value,
                        'remarks' => 'Attended full workshop.',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->actingAs($assignedInstructor)
            ->post(route('instructor.sessions.attendance.submit', $session))
            ->assertRedirect();

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->post(route('admin-vue.attendance.verify', $attendance))
            ->assertRedirect();

        $this->assertSame(AttendanceReviewStatus::Verified, $attendance->fresh()->review_status);

        // Admin approves and publishes the instructor-submitted material & recording
        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->post(route('admin-vue.sessions.materials.review', [$session, $sessionMaterial]), [
                'status' => 'published',
                'review_notes' => 'Approved for session attendees.',
            ])
            ->assertRedirect();

        $this->assertSame(SessionMaterialStatus::Published, $sessionMaterial->fresh()->status);

        $recording->update([
            'encoding_status' => EncodingStatus::Ready,
            'hls_manifest_key' => 'hls/session-'.$session->id.'/stream.m3u8',
        ]);

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->post(route('admin-vue.recordings.approve', $recording))
            ->assertRedirect();

        $this->assertSame(RecordingStatus::Published, $recording->fresh()->status);

        // 6. Verified trainee downloads published session material & sees recording + material in portal; unbooked user gets 403
        $this->actingAs($trainee)->get($sessionMaterial->temporaryDownloadUrl(15))->assertOk();
        $this->actingAs($nonBookedTrainee)->get($sessionMaterial->temporaryDownloadUrl(15))->assertForbidden();

        $this->actingAs($trainee)
            ->get(route('portal.recordings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Recordings/Index')
                ->where('bookingItems.0.isEligible', true)
                ->has('bookingItems.0.recordings', 1)
                ->has('bookingItems.0.materials', 1)
                ->where('bookingItems.0.materials.0.title', 'Floodplain Field Worksheet'),
            );

        // 7. Admin issues a physical session certificate with module_id = null
        $certificate = app(CertificateService::class)->issueCertificate(
            admin: $admin,
            trainee: $trainee,
            recordType: 'session',
            recordId: $session->id,
            completionDate: now(),
        );

        $this->assertInstanceOf(Certificate::class, $certificate);
        $this->assertNull($certificate->module_id);
        $this->assertSame($session->id, $certificate->training_session_id);

        $this->actingAs($trainee)
            ->get(route('portal.certificates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Certificates/Index')
                ->has('issuedCertificates', 1),
            );
    }

    public function test_standalone_learning_module_end_to_end_lifecycle_without_category_or_physical_booking(): void
    {
        Storage::fake('public');
        Storage::fake('materials');
        Storage::fake('videos');
        Queue::fake();

        $admin = $this->createUserWithRole(RoleSlug::Administrator, 'admin-mod@example.com');
        $instructor = $this->createUserWithRole(RoleSlug::Instructor, 'inst-mod@example.com');
        $buyer = $this->createUserWithRole(RoleSlug::Customer, 'buyer-mod@example.com');
        $otherUser = $this->createUserWithRole(RoleSlug::Customer, 'other-mod@example.com');

        // 1. Admin creates standalone module without category or physical session
        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->post(route('admin-vue.modules.store'), [
                'code' => 'MOD-SELF-101',
                'title' => 'Self-Paced Drainage Hydraulics Toolkit',
                'summary' => 'Independent digital workbook and video lessons.',
                'description' => 'Comprehensive self-study curriculum for drainage hydraulics.',
                'independent_price' => 30000,
                'status' => ModuleStatus::Published->value,
            ])
            ->assertRedirect();

        $module = Module::query()->where('code', 'MOD-SELF-101')->firstOrFail();
        $this->assertNull($module->category_id);
        $this->assertTrue($module->is_available_independently);
        $this->assertSame(0, TrainingSession::query()->count(), 'Creating a standalone module must NOT create a physical session.');

        // 2. Admin uploads standalone module material and video lesson
        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->post(route('admin-vue.modules.materials.store', $module), [
                'title' => 'Hydraulics Reference Manual',
                'description' => 'PDF reference guide.',
                'material_type' => 'pdf',
                'status' => MaterialStatus::Published->value,
                'is_downloadable' => true,
                'file' => UploadedFile::fake()->create('hydraulics-manual.pdf', 150, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->post(route('admin-vue.modules.videos.store', $module), [
                'title' => 'Lesson 1: Open Channel Flow',
                'description' => 'Core open-channel formulas.',
                'status' => MaterialStatus::Published->value,
                'file' => UploadedFile::fake()->create('lesson1.mp4', 250, 'video/mp4'),
            ])
            ->assertRedirect();

        $material = ModuleMaterial::query()->where('module_id', $module->id)->firstOrFail();
        $video = ModuleVideo::query()->where('module_id', $module->id)->firstOrFail();
        Queue::assertPushed(ProcessVideoToHlsJob::class);

        // 3. Public standalone module page displays cleanly with no physical sessions mixed in
        $this->get(route('catalogue.modules.show', $module))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Catalogue/Show')
                ->where('module.code', 'MOD-SELF-101')
                ->has('materials', 1)
                ->has('videos', 1)
                ->has('upcomingSessions', 0),
            );

        // Unentitled customer and instructors cannot access standalone module workspace or download its materials
        $this->actingAs($buyer)->get(route('portal.modules.show', $module))->assertForbidden();
        $this->actingAs($buyer)->get(route('materials.download', $material))->assertForbidden();
        $this->actingAs($instructor)->get(route('materials.download', $material))->assertForbidden();

        // Guest visiting module checkout is redirected to login and intended URL is preserved;
        // signed-in buyer can open standalone module checkout even with category_id = null
        auth()->logout();
        $this->get(route('checkout.module.show', $module))->assertRedirect(route('login'));
        $this->actingAs($buyer)
            ->get(route('checkout.module.show', $module))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Checkout/Module')
                ->where('module.code', 'MOD-SELF-101'),
            );

        // 4. Buyer purchases standalone module via Paystack
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/mod-self-101',
                    'access_code' => 'acc_mod_01',
                    'reference' => 'mock-ref',
                ],
            ], 200),
        ]);

        $this->actingAs($buyer)
            ->post(route('checkout.module.store', $module), [
                'phone' => '+2348099998888',
                'billing_address_line1' => '45 Victoria Island',
                'billing_city' => 'Lagos',
                'billing_state' => 'Lagos',
                'billing_postcode' => '101241',
            ])
            ->assertRedirect('https://checkout.paystack.com/mod-self-101');

        $payment = Payment::query()->latest('id')->firstOrFail();

        Http::fake([
            '*/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => $payment->gateway_bill_id,
                    'amount' => 3000000,
                    'currency' => 'NGN',
                    'id' => 654321,
                    'channel' => 'card',
                    'paid_at' => now()->toIso8601String(),
                ],
            ], 200),
        ]);

        app(PaymentOrchestrationService::class)->verifyPaystackPayment($payment);

        // 5. Verify strict separation: entitlement active, zero bookings, zero attendance, zero certificates
        $entitlement = ModuleEntitlement::query()
            ->where('user_id', $buyer->id)
            ->where('module_id', $module->id)
            ->firstOrFail();

        $this->assertSame(EntitlementStatus::Active, $entitlement->status);
        $this->assertSame(OrderStatus::Paid, Order::query()->findOrFail($entitlement->order_id)->status);
        $this->assertSame(0, Booking::query()->count(), 'Standalone module purchase must NOT create any Booking.');
        $this->assertSame(0, Attendance::query()->count(), 'Standalone module purchase must NOT create any Attendance.');
        $this->assertSame(0, Certificate::query()->count());

        // Entitled buyer accesses module workspace & downloads material; non-buyer remains forbidden
        $this->actingAs($buyer)
            ->get(route('portal.modules.show', $module))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Modules/Show')
                ->where('module.code', 'MOD-SELF-101')
                ->has('materials', 1),
            );

        $this->actingAs($buyer)->get(route('materials.download', $material))->assertOk();
        $this->actingAs($otherUser)->get(route('materials.download', $material))->assertForbidden();

        // 6. Admin revokes entitlement via admin-vue governance route -> buyer immediately loses access
        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->post(route('admin-vue.module-entitlements.revoke', $entitlement), [
                'revocation_reason' => 'Refunded standalone purchase.',
            ])
            ->assertSessionHas('success');

        $this->actingAs($buyer)->get(route('portal.modules.show', $module))->assertForbidden();
        $this->actingAs($buyer)->get(route('materials.download', $material))->assertForbidden();
    }
}

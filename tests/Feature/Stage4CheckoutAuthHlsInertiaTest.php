<?php

namespace Tests\Feature;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\EntitlementStatus;
use App\Enums\OrderStatus;
use App\Enums\RoleSlug;
use App\Models\Attendance;
use App\Models\Booking;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\ModuleVideo;
use App\Models\Order;
use App\Models\SessionRecording;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SessionAndBookingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class Stage4CheckoutAuthHlsInertiaTest extends TestCase
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

    public function test_all_seven_auth_and_mfa_routes_render_expected_inertia_components_without_secrets(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->where('submitUrl', route('login.store')),
            )
            ->assertDontSee('D-001');

        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Register')
                ->where('submitUrl', route('register.store')),
            )
            ->assertDontSee('D-001')
            ->assertDontSee('D-023');

        $this->get(route('password.request'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ForgotPassword')
                ->where('submitUrl', route('password.email')),
            );

        $this->get(route('password.reset', ['token' => 'reset-token-123', 'email' => 'trainee@ecohydrotech.com.my']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ResetPassword')
                ->where('token', 'reset-token-123')
                ->where('email', 'trainee@ecohydrotech.com.my'),
            );

        // Unverified customer sees Auth/VerifyEmail
        $unverified = User::factory()->unverified()->create();
        $unverified->assignRole(RoleSlug::Customer);

        $this->actingAs($unverified)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/VerifyEmail')
                ->where('email', $unverified->email),
            );

        // Non-admin blocked from admin MFA setup
        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $this->actingAs($customer)
            ->get(route('two-factor.setup'))
            ->assertForbidden();

        // Admin without MFA sees Auth/TwoFactorSetup
        $adminNoMfa = User::factory()->create();
        $adminNoMfa->assignRole(RoleSlug::Administrator);

        $this->actingAs($adminNoMfa)
            ->get(route('two-factor.setup'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/TwoFactorSetup')
                ->where('secret', fn (?string $s): bool => is_string($s) && $s !== '')
                ->where('qrCodeDataUri', fn (?string $uri): bool => is_string($uri) && str_starts_with($uri, 'data:image/')),
            )
            ->assertDontSee('SRS Section 14');

        // Seeded admin with TOTP sees Auth/TwoFactorChallenge
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('two-factor.challenge'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/TwoFactorChallenge')
                ->where('verifyUrl', route('two-factor.challenge.verify')),
            );
    }

    public function test_inertia_login_and_mfa_redirects_handle_customer_instructor_and_admin_boundaries(): void
    {
        // Customer login redirects normally inside Inertia to /portal
        $this->withHeader('X-Inertia', 'true')
            ->post(route('login.store'), [
                'email' => 'trainee@ecohydrotech.com.my',
                'password' => 'Password123!',
            ])
            ->assertRedirect(route('portal.dashboard'));

        $this->post(route('logout'));

        // Instructor login redirects within Inertia to migrated Instructor Portal
        $this->withHeader('X-Inertia', 'true')
            ->post(route('login.store'), [
                'email' => 'instructor@ecohydrotech.com.my',
                'password' => 'Password123!',
            ])
            ->assertRedirect(route('instructor.dashboard'));

        $this->post(route('logout'));

        // Admin login redirects to Inertia TwoFactorChallenge, then MFA verify returns 409 X-Inertia-Location -> /admin
        $this->withHeader('X-Inertia', 'true')
            ->post(route('login.store'), [
                'email' => 'admin@ecohydrotech.com.my',
                'password' => 'Password123!',
            ])
            ->assertRedirect(route('two-factor.challenge'));

        $otp = (new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP');

        $this->withHeader('X-Inertia', 'true')
            ->post(route('two-factor.challenge.verify'), ['code' => $otp])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('filament.admin.pages.dashboard'));
    }

    public function test_checkout_session_module_and_return_pages_render_inertia_and_redirect_billplz_safely(): void
    {
        Http::fake([
            '*/bills' => Http::sequence()
                ->push([
                    'id' => 'bpz_stage4_ses',
                    'collection_id' => config('services.billplz.collection_id'),
                    'paid' => false,
                    'state' => 'due',
                    'amount' => 89000,
                    'paid_amount' => 0,
                    'url' => 'https://www.billplz-sandbox.com/bills/bpz_stage4_ses',
                ], 200)
                ->push([
                    'id' => 'bpz_stage4_mod',
                    'collection_id' => config('services.billplz.collection_id'),
                    'paid' => false,
                    'state' => 'due',
                    'amount' => 45000,
                    'paid_amount' => 0,
                    'url' => 'https://www.billplz-sandbox.com/bills/bpz_stage4_mod',
                ], 200),
        ]);

        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $session = TrainingSession::query()->where('session_code', 'SES-2026-001')->firstOrFail();
        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();

        $this->actingAs($trainee)
            ->get(route('checkout.session.show', $session))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Checkout/Session')
                ->where('session.sessionCode', 'SES-2026-001')
                ->where('attendee.email', $trainee->email)
                ->where('submitUrl', route('checkout.session.store', $session)),
            );

        $this->actingAs($trainee)
            ->post(route('checkout.session.store', $session), [
                'customer_type' => 'individual',
                'phone' => '+6012-345 6789',
                'billing_address_line1' => 'Level 5, Menara Ecohydrotech',
                'billing_city' => 'Shah Alam',
                'billing_state' => 'Selangor',
                'billing_postcode' => '40000',
                'redirect_gateway' => true,
            ], ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', 'https://www.billplz-sandbox.com/bills/bpz_stage4_ses');

        $this->actingAs($trainee)
            ->get(route('checkout.module.show', $module))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Checkout/Module')
                ->where('module.code', 'EHT-HYD-101')
                ->where('learner.email', $trainee->email)
                ->where('submitUrl', route('checkout.module.store', $module)),
            );

        $this->actingAs($trainee)
            ->post(route('checkout.module.store', $module), [
                'customer_type' => 'individual',
                'phone' => '+6012-345 6789',
                'billing_address_line1' => 'Level 5, Menara Ecohydrotech',
                'billing_city' => 'Shah Alam',
                'billing_state' => 'Selangor',
                'billing_postcode' => '40000',
            ], ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', 'https://www.billplz-sandbox.com/bills/bpz_stage4_mod');

        $latestModuleOrder = Order::query()
            ->where('user_id', $trainee->id)
            ->latest('id')
            ->firstOrFail();

        // /checkout/return NEVER activates access via URL params alone and never exposes gateway_bill_id in props
        $this->actingAs($trainee)
            ->get(route('checkout.return', [
                'order' => $latestModuleOrder->order_number,
                'billplz' => ['id' => 'bpz_stage4_mod', 'paid' => 'true'],
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Checkout/Return')
                ->where('order.orderNumber', $latestModuleOrder->order_number)
                ->where('order.isPendingPayment', true)
                ->where('redirectReportedPaid', true)
                ->missing('payment.gateway_bill_id')
                ->missing('payment.gateway_payload'),
            );

        $this->actingAs($trainee)
            ->get(route('checkout.return', ['order' => $latestModuleOrder->order_number]))
            ->assertOk()
            ->assertDontSee('bpz_stage4_mod');

        $this->assertSame(OrderStatus::PendingPayment, $latestModuleOrder->fresh()->status);
    }

    public function test_module_workspace_and_recording_watch_pages_enforce_entitlements_and_leak_no_storage_keys(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $instructor = User::query()->where('email', 'staff.trainee@ecohydrotech.com.my')->firstOrFail();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();

        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();
        $video = ModuleVideo::query()->where('module_id', $module->id)->firstOrFail();

        // D-018: Unentitled trainee blocked from module workspace
        $this->actingAs($trainee)
            ->get(route('portal.modules.show', $module))
            ->assertForbidden();

        ModuleEntitlement::query()->create([
            'user_id' => $trainee->id,
            'module_id' => $module->id,
            'order_id' => null,
            'order_item_id' => null,
            'grant_source' => 'admin_grant',
            'status' => EntitlementStatus::Active->value,
            'granted_at' => now(),
            'granted_by' => $admin->id,
            'grant_reason' => 'Stage 4 HLS Inertia check.',
        ]);

        $workspaceResp = $this->actingAs($trainee)
            ->get(route('portal.modules.show', $module))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Modules/Show')
                ->where('module.code', 'EHT-HYD-101')
                ->where('activeVideo.id', $video->id)
                ->where('streamManifestUrl', fn (?string $url): bool => is_string($url) && str_contains($url, '/stream/stream.m3u8')),
            );

        foreach (['hls_manifest_key', 'source_storage_key', 'storage_key', 'vid-eht-hyd-101-p1'] as $secret) {
            $workspaceResp->assertDontSee($secret);
        }

        // Switching/watching lesson logs audit event
        $this->actingAs($trainee)
            ->get($video->temporaryWatchUrl(30))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Modules/Show')
                ->where('activeVideo.id', $video->id),
            );

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'module_video.watched',
            'auditable_type' => ModuleVideo::class,
            'auditable_id' => $video->id,
            'actor_id' => $trainee->id,
        ]);

        // D-017 Recording watch requires Verified + Present attendance
        $session3 = TrainingSession::query()->where('session_code', 'SES-2026-003')->firstOrFail();
        $recording = SessionRecording::query()->where('training_session_id', $session3->id)->firstOrFail();

        $this->actingAs($trainee)
            ->get($recording->temporaryWatchUrl(30))
            ->assertForbidden();

        $booking = Booking::query()->where('user_id', $trainee->id)->where('training_session_id', $session3->id)->firstOrFail();
        $attendance = Attendance::query()->where('booking_id', $booking->id)->firstOrFail();
        $attendance->update([
            'attendance_status' => AttendanceStatus::Present->value,
            'recorded_by' => $instructor->id,
            'recorded_at' => now(),
            'review_status' => AttendanceReviewStatus::Verified->value,
            'submitted_by' => $instructor->id,
            'submitted_at' => now(),
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        $recResp = $this->actingAs($trainee)
            ->get($recording->temporaryWatchUrl(30))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Recordings/Watch')
                ->where('recording.id', $recording->id)
                ->where('streamManifestUrl', fn (?string $url): bool => is_string($url) && str_contains($url, '/stream/stream.m3u8')),
            );

        foreach (['hls_manifest_key', 'source_storage_key', 'vid-ses-2026-003-rec1'] as $secret) {
            $recResp->assertDontSee($secret);
        }

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'session_recording.watched',
            'auditable_type' => SessionRecording::class,
            'auditable_id' => $recording->id,
            'actor_id' => $trainee->id,
        ]);
    }
}

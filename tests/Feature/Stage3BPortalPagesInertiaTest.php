<?php

namespace Tests\Feature;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\BookingStatus;
use App\Enums\EntitlementStatus;
use App\Enums\OrderItemType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\SessionStatus;
use App\Models\Attendance;
use App\Models\Booking;
use App\Models\Certificate;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Notification\EtmsNotificationService;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\CertificateSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SessionAndBookingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Stage3BPortalPagesInertiaTest extends TestCase
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
            CertificateSeeder::class,
        ]);
    }

    public function test_all_eleven_stage_3b_portal_routes_render_expected_inertia_components_and_leak_no_secrets(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();

        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();
        ModuleEntitlement::query()->create([
            'user_id' => $trainee->id,
            'module_id' => $module->id,
            'order_id' => null,
            'order_item_id' => null,
            'grant_source' => 'admin_grant',
            'status' => EntitlementStatus::Active->value,
            'granted_at' => now(),
            'granted_by' => $admin->id,
            'grant_reason' => 'Stage 3B Inertia verification grant.',
        ]);

        app(EtmsNotificationService::class)->notifyUser(
            user: $trainee,
            eventType: 'booking.confirmed',
            title: 'Stage 3B Verification Notice',
            message: 'Checking clean Inertia props.',
            actionUrl: route('portal.bookings.index', [], false),
            actionLabel: 'View Bookings',
        );

        $booking = Booking::query()->where('user_id', $trainee->id)->firstOrFail();
        $order = Order::query()->where('user_id', $trainee->id)->firstOrFail();
        $certificate = Certificate::query()->where('user_id', $trainee->id)->firstOrFail();

        $expectations = [
            route('portal.bookings.index') => 'Portal/Bookings/Index',
            route('portal.bookings.show', $booking) => 'Portal/Bookings/Show',
            route('portal.orders.index') => 'Portal/Orders/Index',
            route('portal.orders.show', $order) => 'Portal/Orders/Show',
            route('portal.certificates.index') => 'Portal/Certificates/Index',
            route('portal.certificates.show', $certificate) => 'Portal/Certificates/Show',
            route('portal.modules.index') => 'Portal/Modules/Index',
            route('portal.recordings.index') => 'Portal/Recordings/Index',
            route('portal.notifications.index') => 'Portal/Notifications/Index',
            route('portal.profile.edit') => 'Portal/Profile',
            route('portal.security.edit') => 'Portal/Security',
        ];

        $forbiddenLeaks = [
            'SENSITIVE_TOTP_SECRET_VALUE',
            'pdf_storage_key',
            'verification_token',
            'pdf_checksum_sha256',
            'source_storage_key',
            'hls_manifest_key',
            'gateway_bill_id',
            'gateway_payload',
            'bpz_demo01',
            'D-017',
            'recording_access_policy',
        ];

        foreach ($expectations as $url => $component) {
            $response = $this->actingAs($trainee)
                ->get($url)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component($component)
                    ->where('auth.user.email', $trainee->email),
                );

            foreach ($forbiddenLeaks as $secret) {
                $response->assertDontSee($secret);
            }
        }
    }

    public function test_portal_pages_enforce_strict_ownership_isolation(): void
    {
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $otherTrainee = User::query()->where('email', 'staff.trainee@ecohydrotech.com.my')->firstOrFail();

        $booking = Booking::query()->where('user_id', $trainee->id)->firstOrFail();
        $order = Order::query()->where('user_id', $trainee->id)->firstOrFail();
        $certificate = Certificate::query()->where('user_id', $trainee->id)->firstOrFail();

        app(EtmsNotificationService::class)->notifyUser(
            user: $trainee,
            eventType: 'booking.confirmed',
            title: 'Private Notification Ownership Test',
            message: 'Private content',
        );
        $notification = $trainee->notifications()->latest()->firstOrFail();

        $this->actingAs($otherTrainee)
            ->get(route('portal.bookings.show', $booking))
            ->assertForbidden();

        $this->actingAs($otherTrainee)
            ->get(route('portal.orders.show', $order))
            ->assertForbidden();

        $this->actingAs($otherTrainee)
            ->get(route('portal.certificates.show', $certificate))
            ->assertForbidden();

        $this->actingAs($otherTrainee)
            ->post(route('portal.notifications.read', $notification->id))
            ->assertNotFound();
    }

    public function test_bookings_orders_and_notifications_filters_and_pagination_work_over_inertia(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();

        for ($i = 1; $i <= 11; $i++) {
            $loopSession = TrainingSession::query()->create([
                'module_id' => $module->id,
                'session_code' => sprintf('SES-2026-PG%02d', $i),
                'title' => sprintf('Pagination Session #%02d', $i),
                'slug' => sprintf('pagination-session-%02d', $i),
                'starts_at' => now()->addDays(15 + $i)->setTime(9, 0),
                'ends_at' => now()->addDays(15 + $i)->setTime(17, 0),
                'duration_minutes' => 480,
                'timezone' => 'Asia/Kuala_Lumpur',
                'venue_name' => 'Hall A',
                'venue_address' => 'Menara Ecohydrotech',
                'venue_city' => 'Shah Alam',
                'venue_state' => 'Selangor',
                'capacity' => 25,
                'reserved_seats_count' => 0,
                'confirmed_seats_count' => 0,
                'price' => '890.00',
                'currency' => 'MYR',
                'registration_opens_at' => now()->subDay(),
                'registration_closes_at' => now()->addDays(14 + $i)->setTime(17, 0),
                'recording_access_policy' => 'attendees_only',
                'status' => SessionStatus::Published->value,
                'published_at' => now()->subDay(),
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);

            $order = Order::query()->create([
                'order_number' => sprintf('ORD-2026-PG%04d', $i),
                'user_id' => $trainee->id,
                'order_type' => OrderType::SessionBooking->value,
                'status' => $i === 1 ? OrderStatus::PendingPayment->value : OrderStatus::Paid->value,
                'subtotal_amount' => '890.00',
                'total_amount' => '890.00',
                'currency' => 'MYR',
                'customer_type' => 'individual',
                'customer_name' => $trainee->name,
                'customer_email' => $trainee->email,
                'expires_at' => $i === 1 ? now()->addMinutes(15) : null,
                'paid_at' => $i === 1 ? null : now()->subDays($i),
            ]);

            $item = OrderItem::query()->create([
                'order_id' => $order->id,
                'order_type' => OrderType::SessionBooking->value,
                'item_type' => OrderItemType::TrainingSession->value,
                'training_session_id' => $loopSession->id,
                'module_id' => null,
                'item_code_snapshot' => $loopSession->session_code,
                'item_title_snapshot' => $loopSession->title,
                'unit_price' => '890.00',
                'quantity' => 1,
                'line_total' => '890.00',
                'currency' => 'MYR',
            ]);

            Booking::query()->create([
                'booking_reference' => sprintf('BKG-2026-PG%04d', $i),
                'user_id' => $trainee->id,
                'training_session_id' => $loopSession->id,
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'status' => $i === 1 ? BookingStatus::PendingPayment->value : BookingStatus::Confirmed->value,
                'reserved_at' => now()->subMinutes(2),
                'reservation_expires_at' => now()->addMinutes(13),
                'confirmed_at' => $i === 1 ? null : now()->subDays($i),
            ]);
        }

        // Bookings pagination & status filter
        $this->actingAs($trainee)
            ->get(route('portal.bookings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Bookings/Index')
                ->has('bookings', 10)
                ->where('pagination.total', 12)
                ->where('pagination.lastPage', 2),
            );

        $this->actingAs($trainee)
            ->get(route('portal.bookings.index', ['status' => BookingStatus::PendingPayment->value]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Bookings/Index')
                ->where('statusFilter', BookingStatus::PendingPayment->value)
                ->has('bookings', 1)
                ->where('bookings.0.isHoldActive', true),
            );

        // Orders type + status filter & pagination
        $this->actingAs($trainee)
            ->get(route('portal.orders.index', [
                'type' => OrderType::SessionBooking->value,
                'status' => OrderStatus::PendingPayment->value,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Orders/Index')
                ->where('typeFilter', OrderType::SessionBooking->value)
                ->where('statusFilter', OrderStatus::PendingPayment->value)
                ->has('orders', 1)
                ->where('pagination.total', 1),
            );

        // Notifications unread filter
        app(EtmsNotificationService::class)->notifyUser(
            user: $trainee,
            eventType: 'booking.confirmed',
            title: 'Unread Filter Verification',
            message: 'Filter message',
        );

        $this->actingAs($trainee)
            ->get(route('portal.notifications.index', ['filter' => 'unread']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Notifications/Index')
                ->where('filter', 'unread')
                ->where('unreadCount', fn (int $count): bool => $count >= 1)
                ->where('notifications.0.isUnread', true),
            );
    }

    public function test_order_pay_and_notification_open_respect_inertia_and_blade_boundaries(): void
    {
        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/paystage3b',
                    'access_code' => 'paystage3b',
                    'reference' => 'PAY-STAGE3B-01',
                ],
            ], 200),
        ]);

        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $session = TrainingSession::query()->where('session_code', 'SES-2026-001')->firstOrFail();
        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();

        $pendingOrder = Order::query()->create([
            'order_number' => 'ORD-2026-PAY3B',
            'user_id' => $trainee->id,
            'order_type' => OrderType::SessionBooking->value,
            'status' => OrderStatus::PendingPayment->value,
            'subtotal_amount' => '890.00',
            'total_amount' => '890.00',
            'currency' => 'NGN',
            'customer_type' => 'individual',
            'customer_name' => $trainee->name,
            'customer_email' => $trainee->email,
            'expires_at' => now()->addMinutes(15),
        ]);

        // Inertia XHR gets 409 X-Inertia-Location for the external Paystack URL.
        $this->actingAs($trainee)
            ->withHeader('X-Inertia', 'true')
            ->post(route('portal.orders.pay', $pendingOrder))
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', 'https://checkout.paystack.com/paystage3b');

        // Migrated Stage 4 module workspace target now returns standard Inertia redirect
        app(EtmsNotificationService::class)->notifyUser(
            user: $trainee,
            eventType: 'module.granted',
            title: 'Inertia Module Workspace Notice',
            message: 'Open module workspace.',
            actionUrl: route('portal.modules.show', $module, false),
            actionLabel: 'Open Workspace',
        );
        $moduleNotif = $trainee->notifications()
            ->where('data->title', 'Inertia Module Workspace Notice')
            ->firstOrFail();

        $this->actingAs($trainee)
            ->post(route('portal.notifications.read', ['notification' => $moduleNotif->id, 'open' => 1]), [], ['X-Inertia' => 'true'])
            ->assertRedirect(route('portal.modules.show', $module, false));

        // Unmigrated Instructor target returns 409 X-Inertia-Location when opened from Inertia notification page
        app(EtmsNotificationService::class)->notifyUser(
            user: $trainee,
            eventType: 'system.notice',
            title: 'Admin Cross Boundary Notice',
            message: 'Legacy Blade/Filament boundary check.',
            actionUrl: '/admin',
            actionLabel: 'Open Admin',
        );
        $crossNotif = $trainee->notifications()
            ->where('data->title', 'Admin Cross Boundary Notice')
            ->firstOrFail();

        $this->actingAs($trainee)
            ->post(route('portal.notifications.read', ['notification' => $crossNotif->id, 'open' => 1]), [], ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', '/admin');

        // Migrated Inertia target returns standard redirect when opened from Inertia
        app(EtmsNotificationService::class)->notifyUser(
            user: $trainee,
            eventType: 'booking.confirmed',
            title: 'Inertia Bookings Link Notice',
            message: 'Open bookings list.',
            actionUrl: route('portal.bookings.index', [], false),
            actionLabel: 'Open Bookings',
        );
        $inertiaNotif = $trainee->notifications()
            ->where('data->title', 'Inertia Bookings Link Notice')
            ->firstOrFail();

        $this->actingAs($trainee)
            ->post(route('portal.notifications.read', ['notification' => $inertiaNotif->id, 'open' => 1]), [], ['X-Inertia' => 'true'])
            ->assertRedirect(route('portal.bookings.index', [], false));
    }

    public function test_recordings_eligibility_and_profile_security_updates_preserve_backend_rules(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $instructor = User::query()->where('email', 'staff.trainee@ecohydrotech.com.my')->firstOrFail();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();

        $session3 = TrainingSession::query()->where('session_code', 'SES-2026-003')->firstOrFail();
        $attendance = Attendance::query()->where('training_session_id', $session3->id)->firstOrFail();
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

        $this->actingAs($trainee)
            ->get(route('portal.recordings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Recordings/Index')
                ->where('bookingItems.0.isEligible', true)
                ->where('bookingItems.0.recordings.0.watchUrl', fn (?string $url): bool => is_string($url) && str_contains($url, 'signature=')),
            );

        // Profile validation and persistence
        $this->actingAs($trainee)
            ->from(route('portal.profile.edit'))
            ->put(route('portal.profile.update'), [
                'name' => 'Ahmad Faiz bin Kamaruddin',
                'email' => $trainee->email,
                'phone' => '+6019-245 7710',
                'billing_country' => 'MY',
            ])
            ->assertRedirect(route('portal.profile.edit'))
            ->assertSessionHas('status');

        $this->assertSame('Ahmad Faiz bin Kamaruddin', $trainee->fresh()->name);

        // Security wrong current password returns error; valid password updates hash & emits notification
        $this->actingAs($trainee)
            ->from(route('portal.security.edit'))
            ->put(route('portal.security.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'NewStrongPass123!',
                'password_confirmation' => 'NewStrongPass123!',
            ])
            ->assertRedirect(route('portal.security.edit'))
            ->assertSessionHasErrors('current_password');

        $this->actingAs($trainee)
            ->from(route('portal.security.edit'))
            ->put(route('portal.security.password.update'), [
                'current_password' => 'Password123!',
                'password' => 'NewStrongPass123!',
                'password_confirmation' => 'NewStrongPass123!',
            ])
            ->assertRedirect(route('portal.security.edit'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('NewStrongPass123!', $trainee->fresh()->password));
    }

    public function test_trainee_portal_enforces_entitlement_links_currency_formatting_and_confirmed_recordings_scope(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $booking = Booking::query()->where('booking_reference', 'BKG-2026-DEMO01')->firstOrFail();
        $session = $booking->trainingSession()->with('module')->firstOrFail();
        $order = Order::query()->where('order_number', 'ORD-2026-DEMO01')->firstOrFail();

        // 1. Confirmed physical session booking stays strictly decoupled from standalone module pages,
        //    exposes public session detail URL & recordings URL, and populates instructor professional title.
        $this->actingAs($trainee)
            ->get(route('portal.bookings.show', $booking))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Bookings/Show')
                ->where('booking.session.modulePortalUrl', null)
                ->where('booking.session.moduleCatalogueUrl', null)
                ->where('booking.session.sessionDetailUrl', route('sessions.show', $session))
                ->where('booking.session.recordingsUrl', route('portal.recordings.index'))
                ->where('booking.session.instructors.0.title', fn (?string $title): bool => is_string($title) && $title !== ''),
            );

        // 2. Granting a standalone module entitlement grants portal module access independently without coupling
        //    onto the physical session booking detail view.
        $moduleOrder = Order::query()->create([
            'order_number' => 'ORD-2026-MODCHK',
            'user_id' => $trainee->id,
            'order_type' => OrderType::ModulePurchase->value,
            'status' => OrderStatus::Paid->value,
            'subtotal_amount' => '780.00',
            'total_amount' => '780.00',
            'currency' => 'MYR',
            'customer_type' => 'individual',
            'customer_name' => $trainee->name,
            'customer_email' => $trainee->email,
            'paid_at' => now(),
        ]);

        $moduleOrderItem = OrderItem::query()->create([
            'order_id' => $moduleOrder->id,
            'order_type' => OrderType::ModulePurchase->value,
            'item_type' => OrderItemType::Module->value,
            'module_id' => $session->module_id,
            'item_code_snapshot' => $session->module->code,
            'item_title_snapshot' => $session->module->title,
            'unit_price' => '780.00',
            'quantity' => 1,
            'line_total' => '780.00',
            'currency' => 'MYR',
        ]);

        $activeGrant = ModuleEntitlement::query()->create([
            'user_id' => $trainee->id,
            'module_id' => $session->module_id,
            'order_id' => $moduleOrder->id,
            'order_item_id' => $moduleOrderItem->id,
            'grant_source' => 'purchase',
            'status' => EntitlementStatus::Active->value,
            'granted_at' => now(),
            'granted_by' => $admin->id,
        ]);

        $this->actingAs($trainee)
            ->get(route('portal.bookings.show', $booking))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Bookings/Show')
                ->where('booking.session.modulePortalUrl', null),
            );

        $this->actingAs($trainee)
            ->get(route('portal.modules.show', $session->module))
            ->assertOk();

        // 3. Order detail preserves the stored currency and hides moduleUrl when entitlement is revoked.
        $this->actingAs($trainee)
            ->get(route('portal.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Orders/Show')
                ->where('order.items.0.lineTotalLabel', 'NGN 780.00'),
            );

        $this->actingAs($trainee)
            ->get(route('portal.orders.show', $moduleOrder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Orders/Show')
                ->where('order.items.0.lineTotalLabel', 'MYR 780.00')
                ->where('order.entitlements.0.isActive', true)
                ->where('order.entitlements.0.moduleUrl', route('portal.modules.show', $session->module)),
            );

        $activeGrant->update([
            'status' => EntitlementStatus::Revoked->value,
            'revoked_at' => now(),
            'revoked_by' => $admin->id,
            'revocation_reason' => 'Refunded test revocation.',
        ]);

        $this->actingAs($trainee)
            ->get(route('portal.orders.show', $moduleOrder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Orders/Show')
                ->where('order.entitlements.0.isActive', false)
                ->where('order.entitlements.0.moduleUrl', null),
            );

        // 4. Recordings index excludes expired bookings and provides bookingDetailUrl on confirmed bookings.
        $expiredSession = TrainingSession::query()->where('session_code', 'SES-2026-002')->firstOrFail();
        $expiredOrder = Order::query()->create([
            'order_number' => 'ORD-2026-EXP01',
            'user_id' => $trainee->id,
            'order_type' => OrderType::SessionBooking->value,
            'status' => OrderStatus::Expired->value,
            'subtotal_amount' => $expiredSession->price,
            'total_amount' => $expiredSession->price,
            'currency' => 'MYR',
            'customer_type' => 'individual',
            'customer_name' => $trainee->name,
            'customer_email' => $trainee->email,
            'expires_at' => now()->subHour(),
        ]);

        $expiredOrderItem = OrderItem::query()->create([
            'order_id' => $expiredOrder->id,
            'order_type' => OrderType::SessionBooking->value,
            'item_type' => OrderItemType::TrainingSession->value,
            'training_session_id' => $expiredSession->id,
            'module_id' => null,
            'item_code_snapshot' => $expiredSession->session_code,
            'item_title_snapshot' => $expiredSession->title,
            'unit_price' => $expiredSession->price,
            'quantity' => 1,
            'line_total' => $expiredSession->price,
            'currency' => 'MYR',
        ]);

        Booking::query()->create([
            'booking_reference' => 'BKG-2026-EXP01',
            'user_id' => $trainee->id,
            'training_session_id' => $expiredSession->id,
            'order_id' => $expiredOrder->id,
            'order_item_id' => $expiredOrderItem->id,
            'status' => BookingStatus::Expired->value,
            'reserved_at' => now()->subHours(2),
            'reservation_expires_at' => now()->subHour(),
        ]);

        $this->actingAs($trainee)
            ->get(route('portal.recordings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Recordings/Index')
                ->has('bookingItems', 1)
                ->where('bookingItems.0.bookingReference', 'BKG-2026-DEMO01')
                ->where('bookingItems.0.bookingDetailUrl', route('portal.bookings.show', $booking)),
            )
            ->assertDontSee('BKG-2026-EXP01');
    }
}

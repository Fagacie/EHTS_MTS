<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\EntitlementStatus;
use App\Enums\OrderItemType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\RoleSlug;
use App\Enums\SessionStatus;
use App\Models\Booking;
use App\Models\Certificate;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\CertificateSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SessionAndBookingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortalDashboardRedesignTest extends TestCase
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

    public function test_dashboard_renders_real_kpis_upcoming_sessions_certificates_recent_activity_and_conditional_quick_actions(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $certificate = Certificate::query()->where('user_id', $trainee->id)->firstOrFail();

        // Seeded state: 0 active modules, 1 upcoming confirmed booking (SES-2026-003), 0 completed sessions, 1 certificate
        $this->actingAs($trainee)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Dashboard')
                ->where('portalMeta.userName', $trainee->name)
                ->where('portalMeta.continueLearningUrl', null)
                ->where('stats.activeModulesCount', 0)
                ->where('stats.upcomingBookingsCount', 1)
                ->where('stats.completedSessionsCount', 0)
                ->where('stats.certificatesCount', 1)
                ->has('upcomingBookings', 1)
                ->where('upcomingBookings.0.bookingReference', 'BKG-2026-DEMO01')
                ->where('upcomingBookings.0.sessionTitle', 'Constructed Wetland & Bio-Retention Field Engineering Clinic')
                ->where('upcomingBookings.0.venueName', 'Putrajaya Wetland Engineering Resource Hall')
                ->has('certificates', 1)
                ->where('certificates.0.certificateNumber', $certificate->certificate_number)
                ->where('certificates.0.title', $certificate->training_title_snapshot)
                ->has('recentActivity', 2)
                ->where('recentActivity.0.amountLabel', 'RM 780.00'),
            )
            ->assertDontSee('Continue Learning')
            ->assertDontSee('D-001')
            ->assertDontSee('D-006')
            ->assertDontSee('D-026')
            ->assertDontSee('Account, Profile & Role Readiness');

        // Grant 1 active module entitlement and add a completed past session + module order
        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();

        $moduleOrder = Order::query()->create([
            'order_number' => 'ORD-2026-MOD01',
            'user_id' => $trainee->id,
            'order_type' => OrderType::ModulePurchase->value,
            'status' => OrderStatus::Paid->value,
            'currency' => 'MYR',
            'subtotal_amount' => '320.00',
            'total_amount' => '320.00',
            'customer_name' => $trainee->name,
            'customer_email' => $trainee->email,
            'customer_phone' => '+6019-245 7710',
            'customer_type' => 'individual',
            'paid_at' => now()->subHours(2),
        ]);

        $moduleOrderItem = OrderItem::query()->create([
            'order_id' => $moduleOrder->id,
            'order_type' => OrderType::ModulePurchase->value,
            'item_type' => OrderItemType::Module->value,
            'module_id' => $module->id,
            'item_code_snapshot' => $module->code,
            'item_title_snapshot' => $module->title,
            'unit_price' => '320.00',
            'quantity' => 1,
            'line_total' => '320.00',
        ]);

        ModuleEntitlement::query()->create([
            'user_id' => $trainee->id,
            'module_id' => $module->id,
            'order_id' => $moduleOrder->id,
            'order_item_id' => $moduleOrderItem->id,
            'grant_source' => 'purchase',
            'status' => EntitlementStatus::Active->value,
            'granted_at' => now()->subHours(2),
            'granted_by' => $admin->id,
        ]);

        $completedSession = TrainingSession::query()->create([
            'module_id' => $module->id,
            'session_code' => 'SES-2026-PAST1',
            'title' => 'Completed Drainage Cohort — Archive',
            'slug' => 'completed-drainage-cohort-archive',
            'starts_at' => now()->subDays(10)->setTime(9, 0),
            'ends_at' => now()->subDays(10)->setTime(17, 0),
            'duration_minutes' => 480,
            'timezone' => 'Asia/Kuala_Lumpur',
            'venue_name' => 'Shah Alam Lecture Room',
            'venue_city' => 'Shah Alam',
            'capacity' => 20,
            'reserved_seats_count' => 0,
            'confirmed_seats_count' => 1,
            'registration_closes_at' => now()->subDays(11)->setTime(17, 0),
            'price' => '890.00',
            'currency' => 'MYR',
            'status' => SessionStatus::Completed->value,
            'completed_at' => now()->subDays(9),
            'completed_by' => $admin->id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $pastOrder = Order::query()->create([
            'order_number' => 'ORD-2026-PAST1',
            'user_id' => $trainee->id,
            'order_type' => OrderType::SessionBooking->value,
            'status' => OrderStatus::Paid->value,
            'currency' => 'MYR',
            'subtotal_amount' => '890.00',
            'total_amount' => '890.00',
            'customer_name' => $trainee->name,
            'customer_email' => $trainee->email,
            'customer_phone' => '+6019-245 7710',
            'customer_type' => 'individual',
            'paid_at' => now()->subDays(12),
        ]);

        $pastOrderItem = OrderItem::query()->create([
            'order_id' => $pastOrder->id,
            'order_type' => OrderType::SessionBooking->value,
            'item_type' => OrderItemType::TrainingSession->value,
            'training_session_id' => $completedSession->id,
            'item_code_snapshot' => $completedSession->session_code,
            'item_title_snapshot' => $completedSession->title,
            'unit_price' => '890.00',
            'quantity' => 1,
            'line_total' => '890.00',
        ]);

        Booking::query()->create([
            'booking_reference' => 'BKG-2026-PAST1',
            'user_id' => $trainee->id,
            'training_session_id' => $completedSession->id,
            'order_id' => $pastOrder->id,
            'order_item_id' => $pastOrderItem->id,
            'status' => BookingStatus::Confirmed->value,
            'reserved_at' => now()->subDays(12),
            'reservation_expires_at' => now()->subDays(12)->addMinutes(15),
            'confirmed_at' => now()->subDays(12),
        ]);

        // Add an active 15-minute seat hold on SES-2026-001
        $sessionOne = TrainingSession::query()->where('session_code', 'SES-2026-001')->firstOrFail();

        $holdOrder = Order::query()->create([
            'order_number' => 'ORD-2026-HOLD1',
            'user_id' => $trainee->id,
            'order_type' => OrderType::SessionBooking->value,
            'status' => OrderStatus::PendingPayment->value,
            'currency' => 'MYR',
            'subtotal_amount' => $sessionOne->price,
            'total_amount' => $sessionOne->price,
            'customer_name' => $trainee->name,
            'customer_email' => $trainee->email,
            'customer_phone' => '+6019-245 7710',
            'customer_type' => 'individual',
            'expires_at' => now()->addMinutes(13),
        ]);

        $holdOrderItem = OrderItem::query()->create([
            'order_id' => $holdOrder->id,
            'order_type' => OrderType::SessionBooking->value,
            'item_type' => OrderItemType::TrainingSession->value,
            'training_session_id' => $sessionOne->id,
            'item_code_snapshot' => $sessionOne->session_code,
            'item_title_snapshot' => $sessionOne->title,
            'unit_price' => $sessionOne->price,
            'quantity' => 1,
            'line_total' => $sessionOne->price,
        ]);

        Booking::query()->create([
            'booking_reference' => 'BKG-2026-HOLD1',
            'user_id' => $trainee->id,
            'training_session_id' => $sessionOne->id,
            'order_id' => $holdOrder->id,
            'order_item_id' => $holdOrderItem->id,
            'status' => BookingStatus::PendingPayment->value,
            'reserved_at' => now()->subMinutes(2),
            'reservation_expires_at' => now()->addMinutes(13),
        ]);

        $this->actingAs($trainee)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Dashboard')
                ->where('portalMeta.continueLearningUrl', route('portal.modules.show', $module))
                ->where('stats.activeModulesCount', 1)
                ->where('stats.upcomingBookingsCount', 1)
                ->where('stats.completedSessionsCount', 1)
                ->where('stats.certificatesCount', 1)
                ->where('stats.summaryLine', '1 active module · 1 upcoming session · 1 completed session · 1 certificate earned')
                ->where('activeHoldBooking.bookingReference', 'BKG-2026-HOLD1')
                ->has('upcomingBookings', 1)
                ->where('upcomingBookings.0.bookingReference', 'BKG-2026-DEMO01'),
            )
            ->assertSee('ORD-2026-MOD01')
            ->assertSee('RM 320.00');
    }

    public function test_new_trainee_without_records_sees_clean_zero_kpis_and_empty_states(): void
    {
        $newTrainee = User::factory()->create([
            'name' => 'Aiman Hakim',
            'email_verified_at' => now(),
        ]);
        $newTrainee->assignRole(RoleSlug::Customer);

        $this->actingAs($newTrainee)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Dashboard')
                ->where('portalMeta.userName', 'Aiman Hakim')
                ->where('portalMeta.continueLearningUrl', null)
                ->where('stats.activeModulesCount', 0)
                ->where('stats.upcomingBookingsCount', 0)
                ->where('stats.completedSessionsCount', 0)
                ->where('stats.certificatesCount', 0)
                ->has('upcomingBookings', 0)
                ->has('certificates', 0)
                ->has('recentActivity', 0)
                ->where('activeHoldBooking', null),
            );

        // Single-role instructor remains blocked from trainee dashboard
        $instructor = User::query()->where('email', 'instructor@ecohydrotech.com.my')->firstOrFail();
        $this->actingAs($instructor)
            ->get(route('portal.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_view_has_no_legacy_eht_components_or_prohibited_colours(): void
    {
        $dashboardPath = resource_path('views/portal/dashboard.blade.php');
        $contents = File::get($dashboardPath);

        $this->assertDoesNotMatchRegularExpression(
            '/(x-eht\.|eht-|status-success|portal-indigo|portal-navy|portal-coral|teal-|emerald-|green-|lime-|indigo-|purple-|violet-)/i',
            $contents,
            'Trainee dashboard still contains legacy components or prohibited colour classes.',
        );
    }
}

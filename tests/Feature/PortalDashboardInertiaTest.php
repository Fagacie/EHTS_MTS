<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\CertificateStatus;
use App\Enums\OrderItemType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\RoleSlug;
use App\Enums\SessionStatus;
use App\Models\Booking;
use App\Models\Certificate;
use App\Models\Module;
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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortalDashboardInertiaTest extends TestCase
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

    public function test_unauthenticated_and_non_customer_roles_are_blocked_from_portal_dashboard(): void
    {
        $this->get(route('portal.dashboard'))
            ->assertRedirect(route('login'));

        $instructor = User::query()->where('email', 'instructor@ecohydrotech.com.my')->firstOrFail();
        $this->actingAs($instructor)
            ->get(route('portal.dashboard'))
            ->assertForbidden();

        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->get(route('portal.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_computes_real_six_month_analytics_and_portfolio_mix_for_authenticated_trainee(): void
    {
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $sessionOne = TrainingSession::query()->where('session_code', 'SES-2026-001')->firstOrFail();
        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();

        // Add one paid module purchase in the current month and one unpaid pending order (must NOT be counted as paid spend)
        Order::query()->create([
            'order_number' => 'ORD-2026-ANALYTICS-PAID',
            'user_id' => $trainee->id,
            'order_type' => OrderType::ModulePurchase->value,
            'status' => OrderStatus::Paid->value,
            'currency' => 'MYR',
            'subtotal_amount' => '450.00',
            'total_amount' => '450.00',
            'customer_name' => $trainee->name,
            'customer_email' => $trainee->email,
            'customer_phone' => '+6019-245 7710',
            'customer_type' => 'individual',
            'paid_at' => now(),
        ]);

        Order::query()->create([
            'order_number' => 'ORD-2026-ANALYTICS-PENDING',
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
            'expires_at' => now()->addMinutes(12),
        ]);

        // Add a past paid module order 1 month ago to confirm multi-month aggregation
        $pastPaidOrder = Order::query()->create([
            'order_number' => 'ORD-2026-LASTMO',
            'user_id' => $trainee->id,
            'order_type' => OrderType::ModulePurchase->value,
            'status' => OrderStatus::Paid->value,
            'currency' => 'MYR',
            'subtotal_amount' => '680.00',
            'total_amount' => '680.00',
            'customer_name' => $trainee->name,
            'customer_email' => $trainee->email,
            'customer_phone' => '+6019-245 7710',
            'customer_type' => 'individual',
            'paid_at' => now()->subMonthsNoOverflow(1),
        ]);

        $pastOrderItem = OrderItem::query()->create([
            'order_id' => $pastPaidOrder->id,
            'order_type' => OrderType::ModulePurchase->value,
            'item_type' => OrderItemType::Module->value,
            'module_id' => $module->id,
            'item_code_snapshot' => $module->code,
            'item_title_snapshot' => $module->title,
            'unit_price' => '680.00',
            'quantity' => 1,
            'line_total' => '680.00',
        ]);

        $timezone = (string) config('etms.display_timezone', 'Asia/Kuala_Lumpur');
        $currentMonthKey = now($timezone)->format('Y-m');
        $prevMonthKey = now($timezone)->subMonthsNoOverflow(1)->format('Y-m');

        $response = $this->actingAs($trainee)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Dashboard')
            ->where('stats.pendingPaymentsCount', 1)
            ->where('stats.upcomingBookingsCount', 1)
            ->where('stats.certificatesCount', 1)
            ->has('stats.daysUntilNextSession')
            ->has('monthlyActivity', 6)
            ->where('monthlyActivity', function ($series) use ($currentMonthKey, $prevMonthKey): bool {
                $byKey = collect($series)->keyBy('monthKey');

                $current = $byKey->get($currentMonthKey);
                $prev = $byKey->get($prevMonthKey);

                // Seeded paid order RM 780 + RM 450 = RM 1230 (excludes pending order)
                return $current !== null
                    && (float) $current['paidAmountMyr'] === 1230.0
                    && (int) $current['ordersCount'] === 2
                    && $prev !== null
                    && (float) $prev['paidAmountMyr'] === 680.0
                    && (int) $prev['ordersCount'] === 1;
            })
            ->has('portfolioMix', 4)
            ->where('portfolioMix.1.count', 1)
            ->where('portfolioMix.3.count', 1)
            ->has('calendarEvents', 1)
            ->where('calendarEvents.0.bookingReference', 'BKG-2026-DEMO01')
            ->has('calendarInitialDate')
            ->has('portal.recentNotifications')
            ->missing('portal.learningProgress')
            ->missing('auth.user.password')
            ->missing('auth.user.two_factor_secret')
            ->missing('auth.user.two_factor_recovery_codes')
            ->missing('certificates.0.pdf_storage_key')
            ->missing('certificates.0.verification_token')
            ->missing('certificates.0.pdf_checksum_sha256'),
        );

        // Verify per-user data isolation with a second trainee
        $otherTrainee = User::factory()->create(['email_verified_at' => now()]);
        $otherTrainee->assignRole(RoleSlug::Customer);

        $otherResponse = $this->actingAs($otherTrainee)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Dashboard')
                ->where('stats.activeModulesCount', 0)
                ->where('stats.upcomingBookingsCount', 0)
                ->where('stats.completedSessionsCount', 0)
                ->where('stats.certificatesCount', 0)
                ->where('stats.pendingPaymentsCount', 0)
                ->has('upcomingBookings', 0)
                ->has('calendarEvents', 0)
                ->has('certificates', 0)
                ->has('recentActivity', 0),
            );

        $html = $otherResponse->getContent();
        $this->assertStringNotContainsString('ORD-2026-DEMO01', $html);
        $this->assertStringNotContainsString('BKG-2026-DEMO01', $html);
        $this->assertStringNotContainsString($pastOrderItem->item_code_snapshot, $html);
    }

    public function test_portal_search_enforces_access_control_user_isolation_and_returns_real_records(): void
    {
        $this->get(route('portal.search', ['q' => 'Wetland']))
            ->assertRedirect(route('login'));

        $instructor = User::query()->where('email', 'instructor@ecohydrotech.com.my')->firstOrFail();
        $this->actingAs($instructor)
            ->get(route('portal.search', ['q' => 'Wetland']))
            ->assertForbidden();

        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $certificate = Certificate::query()->where('user_id', $trainee->id)->firstOrFail();

        $shortResponse = $this->actingAs($trainee)
            ->getJson(route('portal.search', ['q' => 'W']))
            ->assertOk();

        $shortResponse->assertJsonPath('totalCount', 0);

        $searchResponse = $this->actingAs($trainee)
            ->getJson(route('portal.search', ['q' => 'Wetland']))
            ->assertOk();

        $cacheControl = (string) $searchResponse->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);

        $searchResponse->assertJsonPath('results.bookings.0.subtitle', 'Booking · BKG-2026-DEMO01');
        $searchResponse->assertJsonPath('results.orders.0.subtitle', 'Order · ORD-2026-DEMO01');
        $searchResponse->assertJsonPath('results.certificates.0.subtitle', "Certificate · {$certificate->certificate_number}");

        $payloadText = $searchResponse->getContent();
        $this->assertStringNotContainsString($certificate->verification_token, $payloadText);
        $this->assertStringNotContainsString((string) $certificate->pdf_storage_key, $payloadText);

        $otherTrainee = User::factory()->create(['email_verified_at' => now()]);
        $otherTrainee->assignRole(RoleSlug::Customer);

        $isolatedResponse = $this->actingAs($otherTrainee)
            ->getJson(route('portal.search', ['q' => 'Wetland']))
            ->assertOk();

        $isolatedResponse->assertJsonCount(0, 'results.bookings');
        $isolatedResponse->assertJsonCount(0, 'results.orders');
        $isolatedResponse->assertJsonCount(0, 'results.certificates');
        $isolatedResponse->assertJsonCount(0, 'results.modules');
    }
}

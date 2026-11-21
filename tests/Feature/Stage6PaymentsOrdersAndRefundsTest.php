<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\EntitlementStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Enums\RefundMethod;
use App\Enums\RefundStatus;
use App\Enums\RoleSlug;
use App\Enums\SessionStatus;
use App\Enums\WebhookProcessingStatus;
use App\Models\Booking;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\ModuleMaterial;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\Refund;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Booking\SessionBookingService;
use App\Services\Payment\BillplzGatewayService;
use App\Services\Payment\RefundService;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SessionAndBookingSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Stage6PaymentsOrdersAndRefundsTest extends TestCase
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

    public function test_billplz_canonical_x_signature_computation_and_constant_time_verification(): void
    {
        $gateway = app(BillplzGatewayService::class);

        $payload = [
            'id' => 'bpz_bill_001',
            'collection_id' => 'inbmmepb',
            'paid' => 'true',
            'state' => 'paid',
            'amount' => '89000',
            'paid_amount' => '89000',
            'due_at' => '2026-12-01',
            'email' => 'trainee@ecohydrotech.com.my',
            'mobile' => '',
            'name' => 'AHMAD FAIZ',
            'url' => 'https://www.billplz-sandbox.com/bills/bpz_bill_001',
            'paid_at' => '2026-11-18 12:00:00 +0800',
        ];

        $source = $gateway->buildCanonicalSignatureSource($payload);
        $this->assertStringStartsWith('amount89000|collection_idinbmmepb|due_at2026-12-01|', $source);
        $this->assertStringContainsString('|mobile|', $source);

        $signature = $gateway->computeCallbackSignature($payload, 'test-secret-key');
        $payloadWithSig = array_merge($payload, ['x_signature' => $signature]);

        $this->assertTrue($gateway->verifyCallbackSignature($payloadWithSig, null, 'test-secret-key'));
        $this->assertFalse($gateway->verifyCallbackSignature($payloadWithSig, 'invalid-signature', 'test-secret-key'));
    }

    public function test_session_checkout_billplz_redirect_and_verified_paid_webhook_confirms_booking_and_seeds_attendance(): void
    {
        Http::fake([
            '*/bills' => Http::response([
                'id' => 'bpz_ses001_paid',
                'collection_id' => config('services.billplz.collection_id'),
                'paid' => false,
                'state' => 'due',
                'amount' => 89000,
                'paid_amount' => 0,
                'url' => 'https://www.billplz-sandbox.com/bills/bpz_ses001_paid',
                'reference_1' => 'PAY-TEST',
            ], 200),
        ]);

        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $session = TrainingSession::query()->where('session_code', 'SES-2026-001')->firstOrFail();

        $this->actingAs($customer)
            ->post(route('checkout.session.store', $session), [
                'customer_type' => 'company_sponsored',
                'phone' => '+6019-245 7710',
                'company_name' => 'LembagaPerunding Hidro Sdn. Bhd.',
            ]);

        $booking = Booking::query()
            ->where('user_id', $customer->id)
            ->where('training_session_id', $session->id)
            ->firstOrFail();

        $order = $booking->order;
        $payment = $order->payments()->firstOrFail();

        $this->assertSame('bpz_ses001_paid', $payment->gateway_bill_id);
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame(BookingStatus::PendingPayment, $booking->status);

        // Browser return page MUST NOT trust query params as payment authority
        $this->actingAs($customer)
            ->get(route('checkout.return', [
                'order' => $order->order_number,
                'billplz' => ['id' => 'bpz_ses001_paid', 'paid' => 'true'],
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Checkout/Return')
                ->where('order.orderNumber', $order->order_number)
                ->where('order.isPendingPayment', true)
                ->where('redirectReportedPaid', true),
            );

        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
        $this->assertSame(BookingStatus::PendingPayment, $booking->fresh()->status);

        // Now send authoritative signed server-to-server Billplz webhook
        $callbackPayload = $this->signedWebhookPayload([
            'id' => 'bpz_ses001_paid',
            'collection_id' => (string) config('services.billplz.collection_id'),
            'paid' => 'true',
            'state' => 'paid',
            'amount' => '89000',
            'paid_amount' => '89000',
            'paid_at' => now()->toIso8601String(),
            'transaction_id' => 'fpx_txn_1001',
            'transaction_status' => 'completed',
        ]);

        $this->post(route('webhooks.billplz'), $callbackPayload)
            ->assertOk()
            ->assertJson(['status' => WebhookProcessingStatus::Processed->value]);

        $this->assertSame(PaymentStatus::Successful, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);

        $session->refresh();
        $this->assertSame(0, $session->reserved_seats_count);
        $this->assertSame(1, $session->confirmed_seats_count);

        $this->assertDatabaseHas('attendance', [
            'training_session_id' => $session->id,
            'booking_id' => $booking->id,
            'user_id' => $customer->id,
            'attendance_status' => 'pending',
            'review_status' => 'draft',
        ]);
    }

    public function test_duplicate_verified_webhook_is_idempotent_and_invalid_signature_does_not_block_later_valid_callback(): void
    {
        Http::fake([
            '*/bills' => Http::response([
                'id' => 'bpz_idemp_001',
                'collection_id' => config('services.billplz.collection_id'),
                'paid' => false,
                'state' => 'due',
                'amount' => 89000,
                'paid_amount' => 0,
                'url' => 'https://www.billplz-sandbox.com/bills/bpz_idemp_001',
            ], 200),
        ]);

        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $session = TrainingSession::query()->where('session_code', 'SES-2026-001')->firstOrFail();

        $this->actingAs($customer)->post(route('checkout.session.store', $session), [
            'customer_type' => 'individual',
        ]);

        $basePayload = [
            'id' => 'bpz_idemp_001',
            'collection_id' => (string) config('services.billplz.collection_id'),
            'paid' => 'true',
            'state' => 'paid',
            'amount' => '89000',
            'paid_amount' => '89000',
            'paid_at' => '2026-11-18 12:15:00 +0800',
        ];

        // 1. Forged / invalid signature must be rejected with 400 and idempotency_key = NULL (D-035)
        $forgedPayload = array_merge($basePayload, ['x_signature' => 'deadbeef0000invalid']);
        $this->post(route('webhooks.billplz'), $forgedPayload)
            ->assertStatus(400)
            ->assertJson(['status' => WebhookProcessingStatus::RejectedInvalidSignature->value]);

        $invalidEvent = PaymentWebhookEvent::query()->latest('id')->firstOrFail();
        $this->assertFalse($invalidEvent->signature_valid);
        $this->assertNull($invalidEvent->idempotency_key);

        // 2. Legitimate signed callback for the exact same bill succeeds (was not blocked by the invalid attempt)
        $validPayload = $this->signedWebhookPayload($basePayload);
        $this->post(route('webhooks.billplz'), $validPayload)
            ->assertOk()
            ->assertJson(['status' => WebhookProcessingStatus::Processed->value]);

        $this->assertSame(1, $session->fresh()->confirmed_seats_count);

        // 3. Replaying the exact same valid callback is ignored as a duplicate
        $this->post(route('webhooks.billplz'), $validPayload)
            ->assertOk()
            ->assertJson(['status' => WebhookProcessingStatus::IgnoredDuplicate->value]);

        $this->assertSame(1, $session->fresh()->confirmed_seats_count);
    }

    public function test_failed_payment_callback_marks_order_failed_and_customer_can_retry_payment(): void
    {
        Http::fake([
            '*/bills' => Http::sequence()
                ->push([
                    'id' => 'bpz_fail_attempt_1',
                    'collection_id' => config('services.billplz.collection_id'),
                    'paid' => false,
                    'state' => 'due',
                    'amount' => 89000,
                    'paid_amount' => 0,
                    'url' => 'https://www.billplz-sandbox.com/bills/bpz_fail_attempt_1',
                ], 200)
                ->push([
                    'id' => 'bpz_retry_attempt_2',
                    'collection_id' => config('services.billplz.collection_id'),
                    'paid' => false,
                    'state' => 'due',
                    'amount' => 89000,
                    'paid_amount' => 0,
                    'url' => 'https://www.billplz-sandbox.com/bills/bpz_retry_attempt_2',
                ], 200),
        ]);

        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $session = TrainingSession::query()->where('session_code', 'SES-2026-001')->firstOrFail();

        $this->actingAs($customer)->post(route('checkout.session.store', $session), [
            'customer_type' => 'individual',
        ]);

        $order = Order::query()->where('user_id', $customer->id)->latest('id')->firstOrFail();
        $firstPayment = $order->payments()->firstOrFail();

        // Send failed payment webhook
        $failedPayload = $this->signedWebhookPayload([
            'id' => 'bpz_fail_attempt_1',
            'collection_id' => (string) config('services.billplz.collection_id'),
            'paid' => 'false',
            'state' => 'due',
            'amount' => '89000',
            'paid_amount' => '0',
            'paid_at' => '',
            'transaction_id' => 'fpx_fail_01',
            'transaction_status' => 'failed',
        ]);

        $this->post(route('webhooks.billplz'), $failedPayload)
            ->assertOk()
            ->assertJson(['status' => WebhookProcessingStatus::Processed->value]);

        $this->assertSame(PaymentStatus::Failed, $firstPayment->fresh()->status);
        $this->assertSame(OrderStatus::Failed, $order->fresh()->status);

        // Customer initiates a retry payment while the 15-minute booking hold is still active
        $this->actingAs($customer)
            ->post(route('portal.orders.pay', $order))
            ->assertRedirect('https://www.billplz-sandbox.com/bills/bpz_retry_attempt_2');

        $this->assertSame(2, $order->payments()->count());
        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
    }

    public function test_late_paid_webhook_on_expired_hold_queues_system_refund_when_capacity_is_taken(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();

        $oneSeatSession = TrainingSession::query()->create([
            'module_id' => $module->id,
            'session_code' => 'SES-2026-LATE1',
            'title' => 'Single-Seat Late Callback Test Session',
            'slug' => 'single-seat-late-callback-test-session',
            'starts_at' => now()->addDays(10)->setTime(9, 0),
            'ends_at' => now()->addDays(10)->setTime(17, 0),
            'duration_minutes' => 480,
            'timezone' => 'Asia/Kuala_Lumpur',
            'venue_name' => 'Hall B',
            'venue_address' => 'Address',
            'venue_city' => 'Shah Alam',
            'venue_state' => 'Selangor',
            'capacity' => 1,
            'reserved_seats_count' => 0,
            'confirmed_seats_count' => 0,
            'price' => '500.00',
            'currency' => 'MYR',
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addDays(8)->setTime(17, 0),
            'recording_access_policy' => 'attendees_only',
            'status' => SessionStatus::Published->value,
            'published_at' => now()->subDay(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        Http::fake([
            '*/bills' => Http::sequence()
                ->push([
                    'id' => 'bpz_late_user1',
                    'collection_id' => config('services.billplz.collection_id'),
                    'paid' => false,
                    'state' => 'due',
                    'amount' => 50000,
                    'paid_amount' => 0,
                    'url' => 'https://www.billplz-sandbox.com/bills/bpz_late_user1',
                ], 200)
                ->push([
                    'id' => 'bpz_user2_seat',
                    'collection_id' => config('services.billplz.collection_id'),
                    'paid' => false,
                    'state' => 'due',
                    'amount' => 50000,
                    'paid_amount' => 0,
                    'url' => 'https://www.billplz-sandbox.com/bills/bpz_user2_seat',
                ], 200),
        ]);

        $customer1 = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $customer2 = User::factory()->create(['email_verified_at' => now()]);
        $customer2->assignRole(RoleSlug::Customer);

        // Customer 1 reserves the only seat, then lets the 15-minute hold expire
        $this->actingAs($customer1)->post(route('checkout.session.store', $oneSeatSession), [
            'customer_type' => 'individual',
        ]);

        $booking1 = Booking::query()
            ->where('user_id', $customer1->id)
            ->where('training_session_id', $oneSeatSession->id)
            ->firstOrFail();

        $booking1->update(['reservation_expires_at' => now()->subMinute()]);
        app(SessionBookingService::class)->reconcileSessionCounters($oneSeatSession->id);

        $this->assertSame(BookingStatus::Expired, $booking1->fresh()->status);

        // Customer 2 books the released seat so the 1-seat session is now full again
        $this->actingAs($customer2)->post(route('checkout.session.store', $oneSeatSession), [
            'customer_type' => 'individual',
        ]);

        $this->assertSame(1, $oneSeatSession->fresh()->reserved_seats_count);

        // Now Customer 1's late paid webhook arrives -> must NOT overbook; must queue a system refund request
        $lateCallback = $this->signedWebhookPayload([
            'id' => 'bpz_late_user1',
            'collection_id' => (string) config('services.billplz.collection_id'),
            'paid' => 'true',
            'state' => 'paid',
            'amount' => '50000',
            'paid_amount' => '50000',
            'paid_at' => now()->toIso8601String(),
        ]);

        $this->post(route('webhooks.billplz'), $lateCallback)->assertOk();

        $this->assertSame(BookingStatus::Expired, $booking1->fresh()->status);
        $this->assertSame(OrderStatus::Paid, $booking1->order->fresh()->status);

        $this->assertDatabaseHas('refunds', [
            'order_id' => $booking1->order_id,
            'booking_id' => $booking1->id,
            'user_id' => $customer1->id,
            'status' => RefundStatus::Requested->value,
            'request_source' => 'system',
            'source_event' => 'late_payment_capacity_unavailable',
        ]);
    }

    public function test_independent_module_purchase_grants_lifetime_entitlement_and_partial_and_full_refunds_update_state(): void
    {
        Http::fake([
            '*/bills' => Http::response([
                'id' => 'bpz_mod_101',
                'collection_id' => config('services.billplz.collection_id'),
                'paid' => false,
                'state' => 'due',
                'amount' => 68000,
                'paid_amount' => 0,
                'url' => 'https://www.billplz-sandbox.com/bills/bpz_mod_101',
            ], 200),
        ]);

        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();
        $material = ModuleMaterial::query()->where('module_id', $module->id)->firstOrFail();

        // Before purchase: material download is forbidden (403)
        $this->actingAs($customer)
            ->get(route('materials.download', $material))
            ->assertForbidden();

        // Customer initiates independent module purchase
        $this->actingAs($customer)
            ->post(route('checkout.module.store', $module), [
                'customer_type' => 'company',
                'company_name' => 'LembagaPerunding Hidro Sdn. Bhd.',
                'phone' => '+6019-245 7710',
            ])
            ->assertRedirect('https://www.billplz-sandbox.com/bills/bpz_mod_101');

        $order = Order::query()
            ->where('user_id', $customer->id)
            ->where('order_type', OrderType::ModulePurchase->value)
            ->firstOrFail();

        $payment = $order->payments()->firstOrFail();
        $this->assertSame('bpz_mod_101', $payment->gateway_bill_id);

        // Verified Billplz paid webhook arrives
        $paidWebhook = $this->signedWebhookPayload([
            'id' => 'bpz_mod_101',
            'collection_id' => (string) config('services.billplz.collection_id'),
            'paid' => 'true',
            'state' => 'paid',
            'amount' => '68000',
            'paid_amount' => '68000',
            'paid_at' => now()->toIso8601String(),
        ]);

        $this->post(route('webhooks.billplz'), $paidWebhook)->assertOk();

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame(PaymentStatus::Successful, $payment->fresh()->status);

        $entitlement = ModuleEntitlement::query()
            ->where('user_id', $customer->id)
            ->where('module_id', $module->id)
            ->firstOrFail();

        $this->assertSame(EntitlementStatus::Active, $entitlement->status);
        $this->assertTrue($customer->fresh()->hasActiveModuleEntitlement($module->id));

        // Entitled customer can now download the private R2 material
        $this->actingAs($customer)
            ->get(route('materials.download', $material))
            ->assertOk();

        // Administrator records a partial refund (RM 200.00 of RM 680.00) -> entitlement stays active
        $refundService = app(RefundService::class);
        $partialRefund = $refundService->recordAndCompleteAdminRefund(
            payment: $payment->fresh(),
            admin: $admin,
            amount: 200.00,
            method: RefundMethod::ManualBankTransfer,
            reason: 'Goodwill partial promotional adjustment.',
            disbursementReference: 'MBB-REF-001',
        );

        $this->assertSame(RefundStatus::Completed, $partialRefund->status);
        $this->assertSame(PaymentStatus::PartiallyRefunded, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PartiallyRefunded, $order->fresh()->status);
        $this->assertSame('200.00', $payment->fresh()->refunded_amount);
        $this->assertSame(EntitlementStatus::Active, $entitlement->fresh()->status);

        // Administrator refunds the remaining RM 480.00 -> full refund revokes module entitlement
        $refundService->recordAndCompleteAdminRefund(
            payment: $payment->fresh(),
            admin: $admin,
            amount: 480.00,
            method: RefundMethod::ManualBankTransfer,
            reason: 'Full order cancellation and refund approved by administrator.',
            disbursementReference: 'MBB-REF-002',
        );

        $this->assertSame(PaymentStatus::Refunded, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Refunded, $order->fresh()->status);
        $this->assertSame('680.00', $payment->fresh()->refunded_amount);
        $this->assertSame(EntitlementStatus::Revoked, $entitlement->fresh()->status);

        // Revoked entitlement blocks material download again (403)
        $this->actingAs($customer->fresh())
            ->get(route('materials.download', $material))
            ->assertForbidden();
    }

    public function test_full_refund_on_confirmed_session_booking_updates_booking_and_releases_confirmed_seat(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $session3 = TrainingSession::query()->where('session_code', 'SES-2026-003')->firstOrFail();
        $booking = Booking::query()->where('booking_reference', 'BKG-2026-DEMO01')->firstOrFail();
        $payment = Payment::query()->where('payment_reference', 'PAY-2026-DEMO01')->firstOrFail();

        $this->assertSame(1, $session3->confirmed_seats_count);

        app(RefundService::class)->recordAndCompleteAdminRefund(
            payment: $payment,
            admin: $admin,
            amount: 780.00,
            method: RefundMethod::PaymentOrder,
            reason: 'Approved participant medical withdrawal refund.',
            disbursementReference: 'BPZ-PO-001',
        );

        $this->assertSame(BookingStatus::Refunded, $booking->fresh()->status);
        $this->assertSame(PaymentStatus::Refunded, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Refunded, $booking->order->fresh()->status);
        $this->assertSame(0, $session3->fresh()->confirmed_seats_count);
    }

    public function test_filament_admin_resources_for_orders_payments_and_refunds_and_cross_user_ownership_constraints(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $otherUser = User::query()->where('email', 'staff.trainee@ecohydrotech.com.my')->firstOrFail();

        $this->actingAs($customer)->get('/admin/orders')->assertForbidden();
        $this->actingAs($customer)->get('/admin/payments')->assertForbidden();
        $this->actingAs($customer)->get('/admin/refunds')->assertForbidden();

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee('ORD-2026-DEMO01');

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->get('/admin/payments')
            ->assertOk()
            ->assertSee('PAY-2026-DEMO01');

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->get('/admin/refunds')
            ->assertOk();

        // Cross-user ownership constraint test (D-032): fk_payments_order_user must reject mismatched user_id
        $customerOrder = Order::query()->where('order_number', 'ORD-2026-DEMO01')->firstOrFail();

        $this->expectException(QueryException::class);

        DB::table('payments')->insert([
            'payment_reference' => 'PAY-BAD-OWNER',
            'order_id' => $customerOrder->id,
            'user_id' => $otherUser->id, // Mismatched user_id != orders.user_id
            'gateway' => 'billplz',
            'amount' => '780.00',
            'refunded_amount' => '0.00',
            'currency' => 'MYR',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function signedWebhookPayload(array $payload): array
    {
        $gateway = app(BillplzGatewayService::class);
        $payload['x_signature'] = $gateway->computeCallbackSignature($payload);

        return $payload;
    }
}

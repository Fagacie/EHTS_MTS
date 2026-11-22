<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\Payment;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Payment\PaymentOrchestrationService;
use App\Services\Payment\RefundService;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SessionAndBookingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaystackPurchaseWorkflowTest extends TestCase
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

    public function test_successful_session_payment_confirms_only_the_physical_booking(): void
    {
        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $session = TrainingSession::query()->where('session_code', 'SES-2026-001')->firstOrFail();
        $session->update(['currency' => 'NGN']);

        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/session-test',
                    'access_code' => 'session-test',
                    'reference' => 'PAY-SESSION-TEST',
                ],
            ], 200),
            '*/transaction/verify/PAY-SESSION-TEST' => Http::response([
                'status' => true,
                'data' => [
                    'id' => 101,
                    'reference' => 'PAY-SESSION-TEST',
                    'status' => 'success',
                    'amount' => 89000,
                    'currency' => 'NGN',
                    'paid_at' => now()->toIso8601String(),
                ],
            ], 200),
        ]);

        $this->actingAs($customer)
            ->post(route('checkout.session.store', $session), [
                'phone' => '+234 801 000 0000',
                'billing_address_line1' => 'Test Address',
                'billing_city' => 'Lagos',
                'billing_state' => 'Lagos',
                'billing_postcode' => '100001',
            ]);

        $booking = Booking::query()
            ->where('user_id', $customer->id)
            ->where('training_session_id', $session->id)
            ->firstOrFail();
        $payment = $booking->order->payments()->firstOrFail();

        app(PaymentOrchestrationService::class)->verifyPaystackPayment($payment);

        $this->assertSame(PaymentStatus::Successful, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Paid, $booking->order->fresh()->status);
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertDatabaseHas('attendance', [
            'booking_id' => $booking->id,
            'user_id' => $customer->id,
        ]);
        $this->assertSame(0, $session->fresh()->reserved_seats_count);
        $this->assertSame(1, $session->fresh()->confirmed_seats_count);
    }

    public function test_successful_document_payment_activates_only_document_entitlement(): void
    {
        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();
        $module->update(['currency' => 'NGN']);
        $bookingCountBefore = Booking::query()->where('user_id', $customer->id)->count();
        $attendanceCountBefore = \App\Models\Attendance::query()->where('user_id', $customer->id)->count();

        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/document-test',
                    'access_code' => 'document-test',
                    'reference' => 'PAY-DOCUMENT-TEST',
                ],
            ], 200),
            '*/transaction/verify/PAY-DOCUMENT-TEST' => Http::response([
                'status' => true,
                'data' => [
                    'id' => 102,
                    'reference' => 'PAY-DOCUMENT-TEST',
                    'status' => 'success',
                    'amount' => 68000,
                    'currency' => 'NGN',
                    'paid_at' => now()->toIso8601String(),
                ],
            ], 200),
        ]);

        $this->actingAs($customer)->post(route('checkout.module.store', $module), [
            'phone' => '+234 801 000 0000',
            'billing_address_line1' => 'Test Address',
            'billing_city' => 'Lagos',
            'billing_state' => 'Lagos',
            'billing_postcode' => '100001',
        ]);

        $payment = Payment::query()->where('gateway_bill_id', 'PAY-DOCUMENT-TEST')->firstOrFail();
        app(PaymentOrchestrationService::class)->verifyPaystackPayment($payment);

        $this->assertSame(PaymentStatus::Successful, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Paid, $payment->order->fresh()->status);
        $this->assertTrue(ModuleEntitlement::query()
            ->where('user_id', $customer->id)
            ->where('module_id', $module->id)
            ->where('status', 'active')
            ->exists());
        $this->assertSame($bookingCountBefore, Booking::query()->where('user_id', $customer->id)->count());
        $this->assertSame($attendanceCountBefore, \App\Models\Attendance::query()->where('user_id', $customer->id)->count());
    }

    public function test_refund_rejects_excess_amount(): void
    {
        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();
        $module->update(['currency' => 'NGN']);
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();

        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/refund-test',
                    'reference' => 'PAY-REFUND-TEST',
                ],
            ], 200),
            '*/transaction/verify/PAY-REFUND-TEST' => Http::response([
                'status' => true,
                'data' => [
                    'id' => 103,
                    'reference' => 'PAY-REFUND-TEST',
                    'status' => 'success',
                    'amount' => 68000,
                    'currency' => 'NGN',
                ],
            ], 200),
            '*/refund' => Http::response([
                'status' => true,
                'data' => [
                    'id' => 9001,
                    'status' => 'processed',
                    'amount' => 1000000,
                ],
            ], 200),
        ]);

        $this->actingAs($customer)->post(route('checkout.module.store', $module));
        $payment = Payment::query()->where('gateway_bill_id', 'PAY-REFUND-TEST')->firstOrFail();
        app(PaymentOrchestrationService::class)->verifyPaystackPayment($payment);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Successful->value,
        ]);

        $refundService = app(RefundService::class);
        $this->expectException(\DomainException::class);
        $refundService->recordAndCompleteAdminRefund(
            payment: $payment->fresh(),
            admin: $admin,
            amount: 1000.00,
            method: 'manual_bank_transfer',
            reason: 'Excess refund test',
        );
    }
}

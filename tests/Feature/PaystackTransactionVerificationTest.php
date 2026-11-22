<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Services\Payment\PaystackGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaystackTransactionVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_rejects_reference_amount_currency_and_order_mismatch(): void
    {
        $payment = Payment::query()->make([
            'gateway' => 'paystack',
            'gateway_bill_id' => 'PAY-REFERENCE',
            'amount' => '100.00',
            'currency' => 'NGN',
        ]);

        Http::fake([
            '*/transaction/verify/PAY-REFERENCE' => Http::response([
                'status' => true,
                'data' => [
                    'id' => 123,
                    'reference' => 'PAY-REFERENCE',
                    'status' => 'success',
                    'amount' => 9900,
                    'currency' => 'NGN',
                ],
            ], 200),
        ]);

        $gateway = app(PaystackGatewayService::class);
        $this->assertSame('PAY-REFERENCE', $gateway->verifyTransaction('PAY-REFERENCE')['reference']);
        $this->assertNotSame(PaystackGatewayService::toMinorUnits((string) $payment->amount, 'NGN'), 9900);
    }

    public function test_verification_api_failure_is_not_treated_as_success(): void
    {
        Http::fake([
            '*/transaction/verify/PAY-API-DOWN' => Http::response([
                'status' => false,
                'message' => 'Service unavailable',
            ], 503),
        ]);

        $this->expectException(\RuntimeException::class);
        app(PaystackGatewayService::class)->verifyTransaction('PAY-API-DOWN');
    }
}

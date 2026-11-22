<?php

namespace Tests\Feature;

use App\Enums\WebhookProcessingStatus;
use App\Services\Payment\PaystackGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaystackWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsigned_paystack_webhook_is_rejected(): void
    {
        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'INVALID-REFERENCE',
                'amount' => 10000,
                'currency' => 'NGN',
                'status' => 'success',
            ],
        ];

        $this->postJson(route('webhooks.paystack'), $payload)
            ->assertStatus(400)
            ->assertJson(['status' => WebhookProcessingStatus::RejectedInvalidSignature->value]);
    }

    public function test_duplicate_signed_legacy_webhook_is_idempotent(): void
    {
        config(['services.paystack.secret_key' => 'sk_test_secret']);

        $rawBody = json_encode([
            'event' => 'ping',
            'data' => [],
        ], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha512', $rawBody, 'sk_test_secret');

        $headers = ['X-Paystack-Signature' => $signature];
        $this->call('POST', route('webhooks.paystack'), [], [], [], [
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $rawBody)->assertOk();

        $this->call('POST', route('webhooks.paystack'), [], [], [], [
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $rawBody)->assertOk();

        $this->assertDatabaseCount('payment_webhook_events', 2);
    }

    public function test_timestamped_signing_header_requires_active_whsec_secret(): void
    {
        config(['services.paystack.webhook_secret' => 'whsec_test_secret']);

        $rawBody = '{"event":"ping","data":[]}';
        $timestamp = time();
        $signature = hash_hmac('sha512', $timestamp.'.'.$rawBody, 'whsec_test_secret');

        $this->call('POST', route('webhooks.paystack'), [], [], [], [
            'HTTP_PAYSTACK_SIGNATURE' => 't='.$timestamp.',v1=whk_test:'.$signature,
            'CONTENT_TYPE' => 'application/json',
        ], $rawBody)->assertOk();
    }
}

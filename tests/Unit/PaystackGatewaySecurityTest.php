<?php

namespace Tests\Unit;

use App\Services\Payment\PaystackGatewayService;
use Tests\TestCase;

class PaystackGatewaySecurityTest extends TestCase
{
    public function test_signed_paystack_header_uses_timestamp_and_constant_time_hmac(): void
    {
        config(['services.paystack.webhook_secret' => 'whsec_test_secret']);

        $rawBody = '{"event":"charge.success","data":{"reference":"PAY-TEST"}}';
        $timestamp = time();
        $signature = hash_hmac('sha512', $timestamp.'.'.$rawBody, 'whsec_test_secret');
        $header = 't='.$timestamp.',v1=whk_test:'.$signature;

        $gateway = app(PaystackGatewayService::class);
        $this->assertTrue($gateway->verifyWebhookSignature($rawBody, $header, null, $timestamp));
        $this->assertFalse($gateway->verifyWebhookSignature($rawBody, str_replace($signature, str_repeat('0', 128), $header), null, $timestamp));
        $this->assertFalse($gateway->verifyWebhookSignature($rawBody, 't='.($timestamp - 301).',v1=whk_test:'.$signature, null, $timestamp));
    }

    public function test_legacy_signature_uses_secret_key_and_unsigned_events_are_rejected(): void
    {
        config(['services.paystack.secret_key' => 'sk_test_secret']);

        $rawBody = '{"event":"charge.success"}';
        $signature = hash_hmac('sha512', $rawBody, 'sk_test_secret');
        $gateway = app(PaystackGatewayService::class);

        $this->assertTrue($gateway->verifyWebhookSignature($rawBody, null, $signature));
        $this->assertFalse($gateway->verifyWebhookSignature($rawBody, null, 'invalid'));
        $this->assertFalse($gateway->verifyWebhookSignature($rawBody, null, null));
    }

    public function test_ngn_amount_is_converted_to_integer_kobo(): void
    {
        $this->assertSame(1000000, PaystackGatewayService::toMinorUnits('10000.00', 'NGN'));
        $this->assertSame('10000.00', PaystackGatewayService::fromMinorUnits(1000000));
    }
}

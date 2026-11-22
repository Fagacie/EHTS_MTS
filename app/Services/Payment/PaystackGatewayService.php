<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackGatewayService
{
    public function initializeTransaction(Payment $payment, Order $order): array
    {
        try {
            $response = $this->request()->post('/transaction/initialize', [
                'email' => $order->customer_email,
                'amount' => self::toMinorUnits((string) $payment->amount, (string) $payment->currency),
                'currency' => (string) $payment->currency,
                'channels' => ['card'],
                'reference' => $payment->payment_reference,
                'callback_url' => route('checkout.return', ['order' => $order->order_number]),
                'metadata' => [
                    'etms_order_number' => $order->order_number,
                    'etms_payment_reference' => $payment->payment_reference,
                ],
            ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Unable to reach Paystack API: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful() || $response->json('status') !== true) {
            throw new RuntimeException('Paystack transaction initialization failed: '.($response->json('message') ?: 'Unexpected API response.'));
        }

        $data = $response->json('data');
        if (! is_array($data) || blank($data['authorization_url'] ?? null) || blank($data['reference'] ?? null)) {
            throw new RuntimeException('Paystack returned an invalid transaction initialization response.');
        }

        return $data;
    }

    public function verifyTransaction(string $reference): array
    {
        try {
            $response = $this->request()->get('/transaction/verify/'.rawurlencode($reference));
        } catch (ConnectionException $e) {
            throw new RuntimeException('Unable to reach Paystack API: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful() || $response->json('status') !== true) {
            throw new RuntimeException('Paystack transaction verification failed: '.($response->json('message') ?: 'Unexpected API response.'));
        }

        $data = $response->json('data');
        if (! is_array($data) || blank($data['reference'] ?? null)) {
            throw new RuntimeException('Paystack returned an invalid transaction verification response.');
        }

        return $data;
    }

    public function refundTransaction(Payment $payment, int $amountMinorUnits): array
    {
        if (blank($payment->gateway_transaction_id)) {
            throw new RuntimeException('Paystack refund requires a verified transaction ID.');
        }

        try {
            $response = $this->request()->post('/refund', [
                'transaction' => $payment->gateway_transaction_id,
                'amount' => $amountMinorUnits,
            ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Unable to reach Paystack API: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful() || $response->json('status') !== true) {
            throw new RuntimeException('Paystack refund failed: '.($response->json('message') ?: 'Unexpected API response.'));
        }

        $data = $response->json('data');
        if (! is_array($data)) {
            throw new RuntimeException('Paystack returned an invalid refund response.');
        }

        return $data;
    }

    public function verifyWebhookSignature(
        string $rawBody,
        ?string $paystackSignature = null,
        ?string $legacySignature = null,
        ?int $now = null,
    ): bool {
        $now ??= time();

        if (blank($paystackSignature)) {
            $legacySecret = (string) config('services.paystack.secret_key');
            $legacySignature = trim((string) $legacySignature);
            if ($legacySecret === '' || $legacySignature === '') {
                return false;
            }

            $expectedLegacy = hash_hmac('sha512', $rawBody, $legacySecret);
            if (strlen($legacySignature) !== 128 || ! ctype_xdigit($legacySignature)) {
                return false;
            }

            return hash_equals($expectedLegacy, strtolower($legacySignature));
        }

        $signingSecret = (string) config('services.paystack.webhook_secret');
        if ($signingSecret === '' || ! str_starts_with($signingSecret, 'whsec_')) {
            return false;
        }

        $parts = [];
        foreach (explode(',', (string) $paystackSignature) as $part) {
            $part = trim($part);
            if (str_contains($part, '=')) {
                [$name, $value] = explode('=', $part, 2);
                $parts[trim($name)][] = trim($value);
            }
        }

        $timestamps = $parts['t'] ?? [];
        $signatures = $parts['v1'] ?? [];
        if ($timestamps === [] || $signatures === []) {
            return false;
        }

        $timestamp = (int) $timestamps[0];
        if ($timestamp <= 0 || abs($now - $timestamp) > 300) {
            return false;
        }

        $signedPayload = $timestamp.'.'.$rawBody;
        $expected = hash_hmac('sha512', $signedPayload, $signingSecret);

        foreach ($signatures as $signature) {
            if (str_contains($signature, ':')) {
                [, $signature] = explode(':', $signature, 2);
            }
            $signature = trim($signature);
            if (strlen($signature) !== 128 || ! ctype_xdigit($signature)) {
                continue;
            }
            if (hash_equals($expected, strtolower($signature))) {
                return true;
            }
        }

        return false;
    }

    public function computeRequestFingerprint(array $payload): string
    {
        return hash('sha256', (string) json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public static function toMinorUnits(string $amount, string $currency): int
    {
        $normalized = trim($amount);
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new RuntimeException('Payment amount must be a non-negative decimal with at most two fraction digits.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        $fraction = str_pad($fraction, 2, '0');

        return ((int) $whole * 100) + (int) substr($fraction, 0, 2);
    }

    public static function fromMinorUnits(int|string $amount): string
    {
        $minorUnits = (int) $amount;

        return number_format($minorUnits / 100, 2, '.', '');
    }

    protected function request()
    {
        try {
            return Http::baseUrl(rtrim((string) config('services.paystack.base_url'), '/'))
                ->withToken((string) config('services.paystack.secret_key'))
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(15);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Unable to reach Paystack API: '.$e->getMessage(), 0, $e);
        }
    }
}

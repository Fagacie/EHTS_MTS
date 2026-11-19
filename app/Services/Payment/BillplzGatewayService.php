<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class BillplzGatewayService
{
    public function createBill(Payment $payment, Order $order): array
    {
        $apiKey = (string) config('services.billplz.api_key');
        $collectionId = (string) config('services.billplz.collection_id');
        $endpoint = rtrim((string) config('services.billplz.endpoint'), '/');
        $amountCents = self::toCents((float) $payment->amount);

        $requestPayload = [
            'collection_id' => $collectionId,
            'email' => $order->customer_email,
            'mobile' => $order->customer_phone ?: '',
            'name' => $order->customer_name,
            'amount' => $amountCents,
            'callback_url' => route('webhooks.billplz'),
            'redirect_url' => route('checkout.return', ['order' => $order->order_number]),
            'description' => "ETMS Order {$order->order_number}",
            'reference_1_label' => 'ETMS Payment Reference',
            'reference_1' => $payment->payment_reference,
        ];

        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->timeout(10)
                ->asForm()
                ->post("{$endpoint}/bills", $requestPayload);

            if ($response->successful()) {
                return $response->json();
            }

            if ($apiKey === 'sandbox-api-key-ecohydrotech') {
                return $this->buildSimulatedSandboxBill($payment, $order, $collectionId, $amountCents);
            }

            throw new RuntimeException('Billplz Create Bill failed with HTTP '.$response->status());
        } catch (ConnectionException $e) {
            if ($apiKey === 'sandbox-api-key-ecohydrotech') {
                return $this->buildSimulatedSandboxBill($payment, $order, $collectionId, $amountCents);
            }

            throw new RuntimeException('Unable to reach Billplz API: '.$e->getMessage(), 0, $e);
        }
    }

    public function getBill(string $billId): array
    {
        $apiKey = (string) config('services.billplz.api_key');
        $endpoint = rtrim((string) config('services.billplz.endpoint'), '/');

        $response = Http::withBasicAuth($apiKey, '')
            ->timeout(10)
            ->get("{$endpoint}/bills/{$billId}");

        if (! $response->successful()) {
            throw new RuntimeException("Billplz Get Bill ({$billId}) failed with HTTP ".$response->status());
        }

        return $response->json();
    }

    public function deleteBill(string $billId): bool
    {
        $apiKey = (string) config('services.billplz.api_key');
        $endpoint = rtrim((string) config('services.billplz.endpoint'), '/');

        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->timeout(10)
                ->delete("{$endpoint}/bills/{$billId}");

            return $response->successful();
        } catch (ConnectionException) {
            return false;
        }
    }

    /**
     * Build the canonical Billplz X-Signature source string:
     * 1. Exclude `x_signature`.
     * 2. Sort parameter names in ascending case-insensitive order.
     * 3. Concatenate each key directly with its value (no `=`).
     * 4. Join components with `|`.
     *
     * @param  array<string, mixed>  $payload
     */
    public function buildCanonicalSignatureSource(array $payload): string
    {
        $filtered = $payload;
        unset($filtered['x_signature']);

        $flattened = [];
        foreach ($filtered as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subVal) {
                    $flattened[$key.$subKey] = $this->stringifySignatureValue($subVal);
                }
            } else {
                $flattened[(string) $key] = $this->stringifySignatureValue($value);
            }
        }

        uksort($flattened, static fn (string $a, string $b): int => strcasecmp($a, $b));

        $parts = [];
        foreach ($flattened as $key => $val) {
            $parts[] = $key.$val;
        }

        return implode('|', $parts);
    }

    /**
     * Compute HMAC-SHA256 Billplz X-Signature.
     *
     * @param  array<string, mixed>  $payload
     */
    public function computeCallbackSignature(array $payload, ?string $xSignatureKey = null): string
    {
        $secret = $xSignatureKey ?? (string) config('services.billplz.x_signature_key');
        $source = $this->buildCanonicalSignatureSource($payload);

        return hash_hmac('sha256', $source, $secret);
    }

    /**
     * Verify Billplz callback X-Signature using constant-time comparison (`hash_equals`).
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyCallbackSignature(
        array $payload,
        ?string $providedSignature = null,
        ?string $xSignatureKey = null,
    ): bool {
        $signature = $providedSignature ?? (string) ($payload['x_signature'] ?? '');
        if ($signature === '') {
            return false;
        }

        $expected = $this->computeCallbackSignature($payload, $xSignatureKey);

        return hash_equals($expected, $signature);
    }

    /**
     * Compute verified-callback idempotency key (`D-035`, Section 6.2).
     * Excludes `x_signature` and optional `transaction_id`/`transaction_status`.
     *
     * @param  array<string, mixed>  $payload
     */
    public function computeVerifiedIdempotencyKey(array $payload): string
    {
        $canonicalFields = [
            'id' => (string) ($payload['id'] ?? ''),
            'collection_id' => (string) ($payload['collection_id'] ?? ''),
            'paid' => $this->normalizeBooleanString($payload['paid'] ?? false),
            'state' => (string) ($payload['state'] ?? ''),
            'amount' => (string) ($payload['amount'] ?? ''),
            'paid_amount' => (string) ($payload['paid_amount'] ?? ''),
            'paid_at' => (string) ($payload['paid_at'] ?? ''),
        ];

        $source = $this->buildCanonicalSignatureSource($canonicalFields);

        return hash('sha256', 'billplz|'.$source);
    }

    /**
     * Compute non-unique request fingerprint over the full received payload including `x_signature`.
     *
     * @param  array<string, mixed>  $payload
     */
    public function computeRequestFingerprint(array $payload): string
    {
        $copy = $payload;
        ksort($copy);

        return hash('sha256', (string) json_encode($copy));
    }

    public static function toCents(float|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function fromCents(int|string $cents): string
    {
        $intCents = (int) $cents;

        return number_format($intCents / 100, 2, '.', '');
    }

    protected function stringifySignatureValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return '';
        }

        return (string) $value;
    }

    protected function normalizeBooleanString(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $lower = strtolower(trim((string) $value));

        return in_array($lower, ['true', '1'], true) ? 'true' : 'false';
    }

    protected function buildSimulatedSandboxBill(
        Payment $payment,
        Order $order,
        string $collectionId,
        int $amountCents,
    ): array {
        $billId = 'bpz_'.strtolower(Str::random(8));

        return [
            'id' => $billId,
            'collection_id' => $collectionId,
            'paid' => false,
            'state' => 'due',
            'amount' => $amountCents,
            'paid_amount' => 0,
            'due_at' => now()->addDay()->toDateString(),
            'email' => $order->customer_email,
            'mobile' => $order->customer_phone,
            'name' => $order->customer_name,
            'url' => route('checkout.return', [
                'order' => $order->order_number,
                'billplz' => [
                    'id' => $billId,
                    'paid' => 'false',
                ],
            ]),
            'reference_1_label' => 'ETMS Payment Reference',
            'reference_1' => $payment->payment_reference,
            'simulated_sandbox' => true,
        ];
    }
}

<?php

namespace App\Services\Payment;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\BookingStatus;
use App\Enums\EntitlementStatus;
use App\Enums\OrderItemType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\SessionStatus;
use App\Enums\WebhookProcessingStatus;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\Refund;
use App\Models\SessionDocumentEntitlement;
use App\Models\SessionMaterial;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Booking\SessionBookingService;
use App\Services\Notification\EtmsNotificationService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class PaymentOrchestrationService
{
    public function __construct(
        protected PaystackGatewayService $paystack,
        protected BillplzGatewayService $billplz,
        protected SessionBookingService $bookingService,
    ) {}

    /**
     * Create an independent module purchase order (`order_type = 'module_purchase'`, D-005, D-018, D-030, D-031).
     *
     * @param  array{
     *     customer_type?: string,
     *     phone?: string|null,
     *     company_name?: string|null,
     *     billing_address?: array<string, mixed>|null
     * }  $billingInput
     */
    public function createModulePurchaseOrder(User $user, Module $module, array $billingInput = []): Order
    {
        if (! $module->isPublished() || ! $module->is_available_independently || $module->independent_price === null) {
            throw new DomainException('This training module is not available for independent purchase.');
        }

        $alreadyEntitled = ModuleEntitlement::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('status', EntitlementStatus::Active->value)
            ->exists();

        if ($alreadyEntitled) {
            throw new DomainException('You already hold an active lifetime entitlement for this training module.');
        }

        $existingPendingOrder = Order::query()
            ->where('user_id', $user->id)
            ->where('order_type', OrderType::ModulePurchase->value)
            ->where('status', OrderStatus::PendingPayment->value)
            ->whereHas('items', fn ($q) => $q->where('module_id', $module->id))
            ->latest('id')
            ->first();

        if ($existingPendingOrder !== null) {
            return $existingPendingOrder->load(['items', 'payments']);
        }

        return DB::transaction(function () use ($user, $module, $billingInput): Order {
            $profile = $user->customerProfile;
            $customerType = 'individual';
            $companyName = null;
            $phone = $billingInput['phone'] ?? $profile?->phone;
            $addr = $billingInput['billing_address'] ?? [];
            $unitPrice = (float) $module->independent_price;

            $order = Order::query()->create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user->id,
                'order_type' => OrderType::ModulePurchase,
                'status' => OrderStatus::PendingPayment,
                'currency' => $module->currency ?: config('etms.currency'),
                'subtotal_amount' => $unitPrice,
                'total_amount' => $unitPrice,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $phone,
                'customer_type' => $customerType,
                'company_name' => $companyName,
                'company_registration_no' => $profile?->company_registration_no,
                'billing_contact_name' => $profile?->billing_contact_name,
                'billing_email' => $profile?->billing_email,
                'billing_phone' => $profile?->billing_phone,
                'billing_address_line_1' => $addr['line1'] ?? $profile?->billing_address_line_1,
                'billing_address_line_2' => $addr['line2'] ?? $profile?->billing_address_line_2,
                'billing_city' => $addr['city'] ?? $profile?->billing_city,
                'billing_state' => $addr['state'] ?? $profile?->billing_state,
                'billing_postcode' => $addr['postcode'] ?? $profile?->billing_postcode,
                'billing_country' => $addr['country'] ?? $profile?->billing_country ?? 'MY',
            ]);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'order_type' => OrderType::ModulePurchase,
                'item_type' => OrderItemType::Module,
                'training_session_id' => null,
                'module_id' => $module->id,
                'item_code_snapshot' => $module->code,
                'item_title_snapshot' => $module->title,
                'unit_price' => $unitPrice,
                'quantity' => 1,
                'line_total' => $unitPrice,
                'currency' => $module->currency ?: config('etms.currency'),
                'metadata_snapshot' => [
                    'category_name' => $module->category?->name,
                    'module_slug' => $module->slug,
                ],
            ]);

            AuditLog::record(
                event: 'order.module_purchase_created',
                auditableType: Order::class,
                auditableId: $order->id,
                oldValues: null,
                newValues: [
                    'order_number' => $order->order_number,
                    'module_id' => $module->id,
                    'module_code' => $module->code,
                    'total_amount' => $order->total_amount,
                ],
                reason: 'Independent module purchase order created.',
                actor: $user,
            );

            return $order->load('items');
        });
    }

    /**
     * Create or reuse a pending payment attempt for an order and persist the gateway reference & checkout URL.
     */
    public function initiatePaymentForOrder(Order $order): Payment
    {
        $order->loadMissing(['bookings.trainingSession', 'payments']);

        if ($order->order_type === OrderType::SessionBooking) {
            $booking = $order->bookings->first();
            if ($booking && $booking->training_session_id) {
                $this->bookingService->reconcileSessionCounters($booking->training_session_id);
                $booking->refresh();
                $order->refresh();
            }

            if ($booking && $booking->status === BookingStatus::Expired) {
                throw new DomainException('Your 15-minute seat reservation has expired. Please book a new seat.');
            }
        }

        if (! in_array($order->status, [OrderStatus::PendingPayment, OrderStatus::Failed], true)) {
            throw new DomainException('This order is not eligible for payment initiation.');
        }

        $existingPending = $order->payments()
            ->where('status', PaymentStatus::Pending->value)
            ->where('gateway', (string) config('services.payment_provider', 'paystack'))
            ->whereNotNull('gateway_payment_url')
            ->first();

        if ($existingPending !== null) {
            return $existingPending;
        }

        /** @var Payment $payment */
        $payment = DB::transaction(function () use ($order): Payment {
            if ($order->status === OrderStatus::Failed) {
                $order->update(['status' => OrderStatus::PendingPayment]);
            }

            return Payment::query()->create([
                'payment_reference' => Payment::generatePaymentReference(),
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'gateway' => (string) config('services.payment_provider', 'paystack'),
                'amount' => $order->total_amount,
                'refunded_amount' => 0.00,
                'currency' => $order->currency ?: config('etms.currency'),
                'status' => PaymentStatus::Pending,
                'expires_at' => $order->expires_at,
            ]);
        });

        $gatewayResponse = $this->paystack->initializeTransaction($payment, $order);

        return DB::transaction(function () use ($payment, $gatewayResponse): Payment {
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status === PaymentStatus::Pending && blank($lockedPayment->gateway_bill_id)) {
                $lockedPayment->update([
                    'gateway_bill_id' => (string) ($gatewayResponse['reference'] ?? ''),
                    'gateway_payment_url' => (string) ($gatewayResponse['authorization_url'] ?? ''),
                    'gateway_reference_1' => (string) ($gatewayResponse['reference'] ?? $lockedPayment->payment_reference),
                    'gateway_state' => 'initialized',
                    'gateway_payload' => $gatewayResponse,
                ]);
            }

            AuditLog::record(
                event: 'payment.initiated',
                auditableType: Payment::class,
                auditableId: $lockedPayment->id,
                oldValues: null,
                newValues: [
                    'payment_reference' => $lockedPayment->payment_reference,
                    'gateway_bill_id' => $lockedPayment->gateway_bill_id,
                    'amount' => $lockedPayment->amount,
                ],
                reason: 'Paystack transaction initialized for order checkout.',
            );

            return $lockedPayment->fresh();
        });
    }

    /**
     * Process an authenticated Paystack webhook without changing ETMS fulfilment rules.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     */
    public function processPaystackWebhook(
        array $payload,
        string $rawBody,
        ?string $signature,
        ?string $legacySignature = null,
        array $headers = [],
        ?string $sourceIp = null,
    ): PaymentWebhookEvent {
        $fingerprint = $this->paystack->computeRequestFingerprint($payload);
        $eventType = (string) ($payload['event'] ?? '');
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $reference = isset($data['reference']) ? (string) $data['reference'] : null;
        $transactionId = isset($data['id']) ? (string) $data['id'] : null;
        $reportedAmount = isset($data['amount']) ? PaystackGatewayService::fromMinorUnits($data['amount']) : null;
        $reportedCurrency = isset($data['currency']) ? strtoupper((string) $data['currency']) : null;
        $status = strtolower((string) ($data['status'] ?? ''));
        $signatureValid = $this->paystack->verifyWebhookSignature($rawBody, $signature, $legacySignature);

        if (! $signatureValid) {
            return PaymentWebhookEvent::query()->create([
                'gateway' => 'paystack',
                'request_fingerprint' => $fingerprint,
                'event_type' => $eventType ?: 'unknown',
                'signature_valid' => false,
                'reported_amount' => $reportedAmount,
                'reported_paid' => false,
                'reported_state' => $status ?: null,
                'reported_currency' => $reportedCurrency,
                'headers' => $headers,
                'payload' => $payload,
                'processing_status' => WebhookProcessingStatus::RejectedInvalidSignature,
                'rejection_reason' => 'Invalid Paystack webhook signature.',
                'source_ip' => $sourceIp,
                'received_at' => now(),
                'processed_at' => now(),
            ]);
        }

        $idempotencyKey = hash('sha256', 'paystack|'.$eventType.'|'.$reference.'|'.$transactionId.'|'.($data['amount'] ?? '').'|'.$status);
        if (PaymentWebhookEvent::query()->where('gateway', 'paystack')->where('idempotency_key', $idempotencyKey)->exists()) {
            return PaymentWebhookEvent::query()->create([
                'gateway' => 'paystack',
                'idempotency_key' => null,
                'request_fingerprint' => $fingerprint,
                'event_type' => $eventType,
                'signature_valid' => true,
                'reported_amount' => $reportedAmount,
                'reported_paid' => $eventType === 'charge.success',
                'reported_state' => $status ?: null,
                'reported_currency' => $reportedCurrency,
                'headers' => $headers,
                'payload' => $payload,
                'processing_status' => WebhookProcessingStatus::IgnoredDuplicate,
                'rejection_reason' => 'Duplicate verified webhook delivery ignored.',
                'source_ip' => $sourceIp,
                'received_at' => now(),
                'processed_at' => now(),
            ]);
        }

        $payment = $reference
            ? Payment::query()->where('gateway', 'paystack')->where('gateway_bill_id', $reference)->first()
            : null;

        if ($payment === null) {
            return PaymentWebhookEvent::query()->create([
                'gateway' => 'paystack',
                'idempotency_key' => $idempotencyKey,
                'request_fingerprint' => $fingerprint,
                'gateway_bill_id' => $reference,
                'gateway_transaction_id' => $transactionId,
                'event_type' => $eventType,
                'signature_valid' => true,
                'reported_amount' => $reportedAmount,
                'reported_paid' => $eventType === 'charge.success',
                'reported_state' => $status ?: null,
                'reported_currency' => $reportedCurrency,
                'headers' => $headers,
                'payload' => $payload,
                'processing_status' => WebhookProcessingStatus::UnmatchedPending,
                'rejection_reason' => 'No local payment matched the Paystack reference.',
                'source_ip' => $sourceIp,
                'received_at' => now(),
            ]);
        }

        $expectedAmount = PaystackGatewayService::toMinorUnits((string) $payment->amount, (string) $payment->currency);
        $receivedAmount = isset($data['amount']) ? (int) $data['amount'] : -1;
        $mismatchReason = $reportedCurrency !== strtoupper((string) $payment->currency)
            ? 'Currency mismatch.'
            : ($receivedAmount !== $expectedAmount ? 'Amount mismatch.' : null);

        if ($mismatchReason !== null) {
            return PaymentWebhookEvent::query()->create([
                'gateway' => 'paystack',
                'idempotency_key' => $idempotencyKey,
                'request_fingerprint' => $fingerprint,
                'gateway_bill_id' => $reference,
                'gateway_transaction_id' => $transactionId,
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'event_type' => $eventType,
                'signature_valid' => true,
                'reported_amount' => $reportedAmount,
                'reported_paid' => $eventType === 'charge.success',
                'reported_state' => $status ?: null,
                'reported_currency' => $reportedCurrency,
                'headers' => $headers,
                'payload' => $payload,
                'processing_status' => WebhookProcessingStatus::RejectedMismatch,
                'rejection_reason' => $mismatchReason,
                'source_ip' => $sourceIp,
                'received_at' => now(),
                'processed_at' => now(),
            ]);
        }

        if ($eventType === 'charge.success') {
            try {
                $this->verifyPaystackPayment($payment);
            } catch (DomainException|RuntimeException $e) {
                return PaymentWebhookEvent::query()->create([
                    'gateway' => 'paystack',
                    'idempotency_key' => $idempotencyKey,
                    'request_fingerprint' => $fingerprint,
                    'gateway_bill_id' => $reference,
                    'gateway_transaction_id' => $transactionId,
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'event_type' => $eventType,
                    'signature_valid' => true,
                    'reported_amount' => $reportedAmount,
                    'reported_paid' => true,
                    'reported_state' => $status ?: null,
                    'reported_currency' => $reportedCurrency,
                    'headers' => $headers,
                    'payload' => $payload,
                    'processing_status' => WebhookProcessingStatus::Failed,
                    'rejection_reason' => 'Paystack server-side verification failed: '.$e->getMessage(),
                    'source_ip' => $sourceIp,
                    'received_at' => now(),
                    'processed_at' => now(),
                ]);
            }
        } elseif (in_array($status, ['failed', 'abandoned'], true)) {
            $this->reconcileVerifiedGatewayState(
                paymentId: $payment->id,
                paid: false,
                state: $status,
                paidAt: null,
                transactionId: $transactionId,
                transactionStatus: $status,
                rawPayload: $payload,
            );
        }

        return PaymentWebhookEvent::query()->create([
            'gateway' => 'paystack',
            'idempotency_key' => $idempotencyKey,
            'request_fingerprint' => $fingerprint,
            'gateway_bill_id' => $reference,
            'gateway_transaction_id' => $transactionId,
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'event_type' => $eventType,
            'signature_valid' => true,
            'reported_amount' => $reportedAmount,
            'reported_paid' => $eventType === 'charge.success',
            'reported_state' => $status ?: null,
            'reported_currency' => $reportedCurrency,
            'headers' => $headers,
            'payload' => $payload,
            'processing_status' => WebhookProcessingStatus::Processed,
            'source_ip' => $sourceIp,
            'received_at' => now(),
            'processed_at' => now(),
        ]);
    }

    public function verifyPaystackPayment(Payment $payment, ?User $actor = null): Payment
    {
        if ($payment->gateway !== 'paystack' || blank($payment->gateway_bill_id)) {
            throw new DomainException('This payment does not have a Paystack reference to verify.');
        }

        $data = $this->paystack->verifyTransaction($payment->gateway_bill_id);
        $amount = isset($data['amount']) ? (int) $data['amount'] : -1;
        $expectedAmount = PaystackGatewayService::toMinorUnits((string) $payment->amount, (string) $payment->currency);
        $currency = strtoupper((string) ($data['currency'] ?? ''));
        $payment->loadMissing('order');
        $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
        $metadataOrder = (string) ($metadata['etms_order_number'] ?? '');

        if ((string) ($data['reference'] ?? '') !== $payment->gateway_bill_id
            || $amount !== $expectedAmount
            || $currency !== strtoupper((string) $payment->currency)
            || ($metadataOrder !== '' && $metadataOrder !== $payment->order->order_number)) {
            throw new DomainException('Paystack verification did not match the expected order, payment amount, currency, or reference.');
        }

        $status = strtolower((string) ($data['status'] ?? ''));
        $this->reconcileVerifiedGatewayState(
            paymentId: $payment->id,
            paid: $status === 'success',
            state: $status === 'success' ? 'paid' : $status,
            paidAt: ! empty($data['paid_at']) ? Carbon::parse((string) $data['paid_at']) : null,
            transactionId: isset($data['id']) ? (string) $data['id'] : $payment->gateway_transaction_id,
            transactionStatus: $status,
            rawPayload: $data,
            actor: $actor,
        );

        return $payment->fresh(['order', 'refunds']);
    }

    /**
     * Process a server-to-server Billplz webhook callback (`POST /webhooks/billplz`, Section 6.2).
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     */
    public function processBillplzWebhook(
        array $payload,
        array $headers = [],
        ?string $sourceIp = null,
    ): PaymentWebhookEvent {
        $fingerprint = $this->billplz->computeRequestFingerprint($payload);
        $billId = isset($payload['id']) ? (string) $payload['id'] : null;
        $collectionId = isset($payload['collection_id']) ? (string) $payload['collection_id'] : null;
        $transactionId = isset($payload['transaction_id']) && $payload['transaction_id'] !== ''
            ? (string) $payload['transaction_id']
            : null;

        $reportedAmount = isset($payload['amount']) && $payload['amount'] !== ''
            ? BillplzGatewayService::fromCents($payload['amount'])
            : null;
        $reportedPaidAmount = isset($payload['paid_amount']) && $payload['paid_amount'] !== ''
            ? BillplzGatewayService::fromCents($payload['paid_amount'])
            : null;
        $reportedPaid = isset($payload['paid'])
            ? in_array(strtolower(trim((string) $payload['paid'])), ['true', '1'], true)
            : null;
        $reportedState = isset($payload['state']) ? (string) $payload['state'] : null;
        $reportedPaidAt = ! empty($payload['paid_at'])
            ? Carbon::parse((string) $payload['paid_at'])
            : null;

        $signatureValid = $this->billplz->verifyCallbackSignature($payload);

        // 1. Reject invalid X-Signature (idempotency_key = NULL so invalid attempts never block valid keys, D-035)
        if (! $signatureValid) {
            $event = PaymentWebhookEvent::query()->create([
                'gateway' => 'billplz',
                'idempotency_key' => null,
                'request_fingerprint' => $fingerprint,
                'gateway_bill_id' => $billId,
                'gateway_collection_id' => $collectionId,
                'gateway_transaction_id' => $transactionId,
                'payment_id' => null,
                'order_id' => null,
                'event_type' => 'bill.callback',
                'signature_valid' => false,
                'reported_amount' => $reportedAmount,
                'reported_paid_amount' => $reportedPaidAmount,
                'reported_paid' => $reportedPaid,
                'reported_state' => $reportedState,
                'reported_paid_at' => $reportedPaidAt,
                'reported_currency' => 'MYR',
                'headers' => $headers,
                'payload' => $payload,
                'processing_status' => WebhookProcessingStatus::RejectedInvalidSignature,
                'rejection_reason' => 'Invalid Billplz HMAC-SHA256 X-Signature.',
                'source_ip' => $sourceIp,
                'received_at' => now(),
                'processed_at' => now(),
            ]);

            AuditLog::record(
                event: 'payment.webhook_invalid_signature',
                auditableType: PaymentWebhookEvent::class,
                auditableId: $event->id,
                oldValues: null,
                newValues: ['gateway_bill_id' => $billId, 'source_ip' => $sourceIp],
                reason: 'Rejected Billplz webhook with invalid X-Signature.',
            );

            return $event;
        }

        // 2. Verified signature: compute canonical idempotency key and check for duplicate delivery
        $idempotencyKey = $this->billplz->computeVerifiedIdempotencyKey($payload);

        $alreadyProcessed = PaymentWebhookEvent::query()
            ->where('gateway', 'billplz')
            ->where('idempotency_key', $idempotencyKey)
            ->exists();

        if ($alreadyProcessed) {
            return PaymentWebhookEvent::query()->create([
                'gateway' => 'billplz',
                'idempotency_key' => null,
                'request_fingerprint' => $fingerprint,
                'gateway_bill_id' => $billId,
                'gateway_collection_id' => $collectionId,
                'gateway_transaction_id' => $transactionId,
                'payment_id' => null,
                'order_id' => null,
                'event_type' => 'bill.callback',
                'signature_valid' => true,
                'reported_amount' => $reportedAmount,
                'reported_paid_amount' => $reportedPaidAmount,
                'reported_paid' => $reportedPaid,
                'reported_state' => $reportedState,
                'reported_paid_at' => $reportedPaidAt,
                'reported_currency' => 'MYR',
                'headers' => $headers,
                'payload' => $payload,
                'processing_status' => WebhookProcessingStatus::IgnoredDuplicate,
                'rejection_reason' => 'Duplicate verified callback delivery ignored.',
                'source_ip' => $sourceIp,
                'received_at' => now(),
                'processed_at' => now(),
            ]);
        }

        // 3. Match internal Payment by gateway_bill_id
        $payment = $billId !== null
            ? Payment::query()->where('gateway', 'billplz')->where('gateway_bill_id', $billId)->first()
            : null;

        if ($payment === null) {
            return PaymentWebhookEvent::query()->create([
                'gateway' => 'billplz',
                'idempotency_key' => $idempotencyKey,
                'request_fingerprint' => $fingerprint,
                'gateway_bill_id' => $billId,
                'gateway_collection_id' => $collectionId,
                'gateway_transaction_id' => $transactionId,
                'payment_id' => null,
                'order_id' => null,
                'event_type' => 'bill.callback',
                'signature_valid' => true,
                'reported_amount' => $reportedAmount,
                'reported_paid_amount' => $reportedPaidAmount,
                'reported_paid' => $reportedPaid,
                'reported_state' => $reportedState,
                'reported_paid_at' => $reportedPaidAt,
                'reported_currency' => 'MYR',
                'headers' => $headers,
                'payload' => $payload,
                'processing_status' => WebhookProcessingStatus::UnmatchedPending,
                'rejection_reason' => 'No local payment row matched gateway_bill_id yet.',
                'source_ip' => $sourceIp,
                'received_at' => now(),
            ]);
        }

        // 4. Validate collection_id and amount in integer cents
        $expectedCollectionId = (string) config('services.billplz.collection_id');
        $expectedCents = BillplzGatewayService::toCents((float) $payment->amount);
        $reportedCents = isset($payload['amount']) ? (int) $payload['amount'] : -1;
        $reportedPaidCents = isset($payload['paid_amount']) ? (int) $payload['paid_amount'] : 0;

        $mismatchReason = null;
        if ($expectedCollectionId !== '' && $collectionId !== null && $collectionId !== $expectedCollectionId) {
            $mismatchReason = "Collection ID mismatch: expected {$expectedCollectionId}, received {$collectionId}.";
        } elseif ($reportedCents !== $expectedCents) {
            $mismatchReason = "Amount mismatch: expected {$expectedCents} sen, received {$reportedCents} sen.";
        } elseif ($reportedPaid === true && ($reportedState !== 'paid' || $reportedPaidCents !== $expectedCents)) {
            $mismatchReason = 'Inconsistent paid flag, state, or paid_amount in callback.';
        }

        if ($mismatchReason !== null) {
            $event = PaymentWebhookEvent::query()->create([
                'gateway' => 'billplz',
                'idempotency_key' => $idempotencyKey,
                'request_fingerprint' => $fingerprint,
                'gateway_bill_id' => $billId,
                'gateway_collection_id' => $collectionId,
                'gateway_transaction_id' => $transactionId,
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'event_type' => 'bill.callback',
                'signature_valid' => true,
                'reported_amount' => $reportedAmount,
                'reported_paid_amount' => $reportedPaidAmount,
                'reported_paid' => $reportedPaid,
                'reported_state' => $reportedState,
                'reported_paid_at' => $reportedPaidAt,
                'reported_currency' => 'MYR',
                'headers' => $headers,
                'payload' => $payload,
                'processing_status' => WebhookProcessingStatus::RejectedMismatch,
                'rejection_reason' => $mismatchReason,
                'source_ip' => $sourceIp,
                'received_at' => now(),
                'processed_at' => now(),
            ]);

            AuditLog::record(
                event: 'payment.webhook_mismatch',
                auditableType: Payment::class,
                auditableId: $payment->id,
                oldValues: null,
                newValues: ['reason' => $mismatchReason],
                reason: $mismatchReason,
            );

            return $event;
        }

        // 5. Execute authoritative state transition under the global locking hierarchy
        $this->reconcileVerifiedGatewayState(
            paymentId: $payment->id,
            paid: (bool) $reportedPaid,
            state: (string) $reportedState,
            paidAt: $reportedPaidAt,
            transactionId: $transactionId,
            transactionStatus: isset($payload['transaction_status']) ? (string) $payload['transaction_status'] : null,
            rawPayload: $payload,
        );

        return PaymentWebhookEvent::query()->create([
            'gateway' => 'billplz',
            'idempotency_key' => $idempotencyKey,
            'request_fingerprint' => $fingerprint,
            'gateway_bill_id' => $billId,
            'gateway_collection_id' => $collectionId,
            'gateway_transaction_id' => $transactionId,
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'event_type' => 'bill.callback',
            'signature_valid' => true,
            'reported_amount' => $reportedAmount,
            'reported_paid_amount' => $reportedPaidAmount,
            'reported_paid' => $reportedPaid,
            'reported_state' => $reportedState,
            'reported_paid_at' => $reportedPaidAt,
            'reported_currency' => 'MYR',
            'headers' => $headers,
            'payload' => $payload,
            'processing_status' => WebhookProcessingStatus::Processed,
            'source_ip' => $sourceIp,
            'received_at' => now(),
            'processed_at' => now(),
        ]);
    }

    /**
     * Server-side Billplz API verification (`GET /api/v3/bills/{id}`) for admin or return-page reconciliation.
     */
    public function verifyPaymentViaApi(Payment $payment, ?User $actor = null): Payment
    {
        if (blank($payment->gateway_bill_id)) {
            throw new DomainException('This payment attempt does not have a Billplz Bill ID to verify.');
        }

        $billData = $this->billplz->getBill($payment->gateway_bill_id);

        $paid = (bool) ($billData['paid'] ?? false);
        $state = (string) ($billData['state'] ?? 'due');
        $paidAt = ! empty($billData['paid_at']) ? Carbon::parse((string) $billData['paid_at']) : null;

        $this->reconcileVerifiedGatewayState(
            paymentId: $payment->id,
            paid: $paid,
            state: $state,
            paidAt: $paidAt,
            transactionId: $payment->gateway_transaction_id,
            transactionStatus: null,
            rawPayload: $billData,
            actor: $actor,
        );

        return $payment->fresh(['order', 'refunds']);
    }

    /**
     * Reconcile payment, order, booking/entitlement, and session capacity under the global lock hierarchy (Section 5.9.1).
     *
     * @param  array<string, mixed>  $rawPayload
     */
    public function reconcileVerifiedGatewayState(
        int $paymentId,
        bool $paid,
        string $state,
        ?Carbon $paidAt,
        ?string $transactionId,
        ?string $transactionStatus,
        array $rawPayload,
        ?User $actor = null,
    ): void {
        // Preliminary non-locking lookup to determine order type and session ID for lock hierarchy ordering
        $prelimPayment = Payment::query()->with(['order.items', 'order.bookings'])->findOrFail($paymentId);
        $prelimOrder = $prelimPayment->order;
        $sessionId = $prelimOrder->bookings->first()?->training_session_id
            ?? $prelimOrder->items->first()?->training_session_id;

        DB::transaction(function () use (
            $paymentId,
            $prelimOrder,
            $sessionId,
            $paid,
            $state,
            $paidAt,
            $transactionId,
            $transactionStatus,
            $rawPayload,
            $actor,
        ): void {
            // 1. Lock training_sessions first (if session_booking workflow)
            $lockedSession = null;
            if ($sessionId !== null) {
                $lockedSession = TrainingSession::query()
                    ->whereKey($sessionId)
                    ->lockForUpdate()
                    ->first();
            }

            // 2. Lock orders
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->with('items')
                ->whereKey($prelimOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 3. Lock payments
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::query()
                ->whereKey($paymentId)
                ->lockForUpdate()
                ->firstOrFail();

            $now = now();
            $effectivePaidAt = $paidAt ?? $now;

            if ($paid && $state === 'paid') {
                // If this payment was already marked successful/refunded, nothing to repeat
                if (in_array($lockedPayment->status, [
                    PaymentStatus::Successful,
                    PaymentStatus::Refunded,
                    PaymentStatus::PartiallyRefunded,
                ], true)) {
                    return;
                }

                $oldPaymentStatus = $lockedPayment->status->value;
                $oldOrderStatus = $lockedOrder->status->value;

                $lockedPayment->update([
                    'status' => PaymentStatus::Successful,
                    'gateway_state' => 'paid',
                    'gateway_transaction_id' => $transactionId ?? $lockedPayment->gateway_transaction_id,
                    'gateway_payload' => $rawPayload,
                    'paid_at' => $effectivePaidAt,
                ]);

                // If order was already paid by a different payment attempt, queue this duplicate paid bill for refund review
                if (in_array($lockedOrder->status, [OrderStatus::Paid, OrderStatus::Refunded, OrderStatus::PartiallyRefunded], true)) {
                    Refund::query()->create([
                        'refund_reference' => Refund::generateRefundReference(),
                        'order_id' => $lockedOrder->id,
                        'payment_id' => $lockedPayment->id,
                        'booking_id' => $lockedOrder->bookings()->value('id'),
                        'user_id' => $lockedOrder->user_id,
                        'amount' => $lockedPayment->amount,
                        'currency' => $lockedPayment->currency,
                        'reason' => 'Duplicate paid Billplz bill received for an already-paid order.',
                        'status' => RefundStatus::Requested,
                        'request_source' => 'system',
                        'requested_by' => null,
                        'source_event' => 'duplicate_paid_bill',
                        'requested_at' => $now,
                    ]);

                    return;
                }

                $lockedOrder->update([
                    'status' => OrderStatus::Paid,
                    'paid_at' => $effectivePaidAt,
                ]);

                if ($lockedOrder->order_type === OrderType::SessionBooking && $lockedSession !== null) {
                    $this->fulfilSessionOrderLocked($lockedSession, $lockedOrder, $lockedPayment, $now);
                } elseif ($lockedOrder->order_type === OrderType::ModulePurchase) {
                    $this->fulfilModuleOrderLocked($lockedOrder, $now);
                }

                AuditLog::record(
                    event: 'payment.succeeded',
                    auditableType: Payment::class,
                    auditableId: $lockedPayment->id,
                    oldValues: [
                        'payment_status' => $oldPaymentStatus,
                        'order_status' => $oldOrderStatus,
                    ],
                    newValues: [
                        'payment_status' => PaymentStatus::Successful->value,
                        'order_status' => OrderStatus::Paid->value,
                        'gateway_bill_id' => $lockedPayment->gateway_bill_id,
                    ],
                    reason: 'Verified Billplz paid callback processed.',
                    actor: $actor,
                );

                $notifiedOrder = $lockedOrder->fresh(['user']);
                $paidAmount = number_format((float) $lockedPayment->amount, 2);
                $paymentRef = $lockedPayment->payment_reference ?? $lockedPayment->gateway_bill_id;

                DB::afterCommit(function () use ($notifiedOrder, $paidAmount, $paymentRef): void {
                    if (! $notifiedOrder?->user) {
                        return;
                    }

                    app(EtmsNotificationService::class)->notifyUser(
                        user: $notifiedOrder->user,
                        eventType: 'payment.succeeded',
                        title: "Payment Confirmed: {$notifiedOrder->order_number}",
                        message: "We received your payment of MYR {$paidAmount} for order {$notifiedOrder->order_number}.",
                        actionUrl: route('portal.orders.show', $notifiedOrder, false),
                        actionLabel: 'View Order Receipt',
                        details: [
                            'Order Number' => $notifiedOrder->order_number,
                            'Amount Paid' => "MYR {$paidAmount}",
                            'Gateway Reference' => (string) $paymentRef,
                        ],
                        reference: $notifiedOrder->order_number,
                    );
                });

                return;
            }

            // Unpaid / Failed / Deleted callback handling
            if (in_array($lockedPayment->status, [
                PaymentStatus::Successful,
                PaymentStatus::Refunded,
                PaymentStatus::PartiallyRefunded,
            ], true)) {
                return;
            }

            if ($state === 'deleted' || $transactionStatus === 'failed' || $state === 'failed') {
                $newPaymentStatus = $state === 'deleted' ? PaymentStatus::Cancelled : PaymentStatus::Failed;

                $lockedPayment->update([
                    'status' => $newPaymentStatus,
                    'gateway_state' => $state,
                    'gateway_transaction_id' => $transactionId ?? $lockedPayment->gateway_transaction_id,
                    'gateway_payload' => $rawPayload,
                    'failed_at' => $newPaymentStatus === PaymentStatus::Failed ? $now : $lockedPayment->failed_at,
                    'cancelled_at' => $newPaymentStatus === PaymentStatus::Cancelled ? $now : $lockedPayment->cancelled_at,
                ]);

                if ($lockedOrder->status === OrderStatus::PendingPayment) {
                    $lockedOrder->update([
                        'status' => OrderStatus::Failed,
                    ]);
                }

                AuditLog::record(
                    event: 'payment.failed',
                    auditableType: Payment::class,
                    auditableId: $lockedPayment->id,
                    oldValues: null,
                    newValues: [
                        'payment_status' => $newPaymentStatus->value,
                        'gateway_state' => $state,
                    ],
                    reason: 'Billplz reported unpaid/failed/deleted payment attempt.',
                    actor: $actor,
                );

                $failedOrder = $lockedOrder->fresh(['user']);
                $failedAmount = number_format((float) $lockedPayment->amount, 2);
                $isCancelledState = $newPaymentStatus === PaymentStatus::Cancelled;

                DB::afterCommit(function () use ($failedOrder, $failedAmount, $isCancelledState): void {
                    if (! $failedOrder?->user) {
                        return;
                    }

                    app(EtmsNotificationService::class)->notifyUser(
                        user: $failedOrder->user,
                        eventType: 'payment.failed',
                        title: ($isCancelledState ? 'Payment Cancelled: ' : 'Payment Failed: ').$failedOrder->order_number,
                        message: 'Your payment attempt of MYR '.$failedAmount.' for order '.$failedOrder->order_number.' could not be completed. Please review the order status in your portal.',
                        actionUrl: route('portal.orders.show', $failedOrder, false),
                        actionLabel: 'Review Order & Payment',
                        details: [
                            'Order Number' => $failedOrder->order_number,
                            'Amount' => "MYR {$failedAmount}",
                            'Status' => $isCancelledState ? 'Cancelled at Gateway' : 'Failed',
                        ],
                        reference: $failedOrder->order_number,
                    );
                });
            } else {
                $lockedPayment->update([
                    'gateway_state' => $state,
                    'gateway_payload' => $rawPayload,
                ]);
            }
        });
    }

    /**
     * Fulfil a physical session booking order (including late-payment evaluation per Section 6.2).
     */
    protected function fulfilSessionOrderLocked(
        TrainingSession $lockedSession,
        Order $lockedOrder,
        Payment $lockedPayment,
        Carbon $now,
    ): void {
        /** @var Booking|null $lockedBooking */
        $lockedBooking = Booking::query()
            ->where('order_id', $lockedOrder->id)
            ->lockForUpdate()
            ->first();

        if (! $lockedBooking) {
            return;
        }

        // Case 1: Active pending_payment hold -> confirm immediately
        if ($lockedBooking->status === BookingStatus::PendingPayment) {
            $lockedBooking->update([
                'status' => BookingStatus::Confirmed,
                'confirmed_at' => $now,
            ]);

            Attendance::query()->firstOrCreate(
                [
                    'training_session_id' => $lockedSession->id,
                    'booking_id' => $lockedBooking->id,
                ],
                [
                    'user_id' => $lockedBooking->user_id,
                    'attendance_status' => AttendanceStatus::Pending,
                    'review_status' => AttendanceReviewStatus::Draft,
                ],
            );

            $this->recomputeSessionCountersLocked($lockedSession, $now);

            $confirmedBooking = $lockedBooking->fresh(['user', 'trainingSession']);
            DB::afterCommit(function () use ($confirmedBooking): void {
                if (! $confirmedBooking?->user || ! $confirmedBooking->trainingSession) {
                    return;
                }

                $session = $confirmedBooking->trainingSession;
                app(EtmsNotificationService::class)->notifyUser(
                    user: $confirmedBooking->user,
                    eventType: 'booking.confirmed',
                    title: "Booking Confirmed: {$session->title}",
                    message: "Your seat reservation ({$confirmedBooking->booking_reference}) for {$session->title} is now confirmed.",
                    actionUrl: route('portal.bookings.show', $confirmedBooking, false),
                    actionLabel: 'View Booking Pass',
                    details: [
                        'Booking Reference' => $confirmedBooking->booking_reference,
                        'Session Code' => $session->session_code,
                        'Starts (MYT)' => $session->starts_at?->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))->format('d M Y, h:i A') ?? 'TBA',
                        'Venue' => trim("{$session->venue_name}, {$session->venue_city}", ', '),
                    ],
                    reference: $confirmedBooking->booking_reference,
                );
            });

            return;
        }

        // Case 2: Late payment arriving after 15-minute hold expired (Section 6.2)
        if ($lockedBooking->status === BookingStatus::Expired) {
            $activeReserved = Booking::query()
                ->where('training_session_id', $lockedSession->id)
                ->where('status', BookingStatus::PendingPayment->value)
                ->where('reservation_expires_at', '>', $now)
                ->count();

            $activeConfirmed = Booking::query()
                ->where('training_session_id', $lockedSession->id)
                ->where('status', BookingStatus::Confirmed->value)
                ->count();

            $sessionOpenStatus = in_array($lockedSession->status, [
                SessionStatus::Published,
                SessionStatus::Full,
            ], true);

            $beforeRegClose = $lockedSession->registration_closes_at === null
                || $now->lt($lockedSession->registration_closes_at);
            $beforeStart = $lockedSession->starts_at === null
                || $now->lt($lockedSession->starts_at);
            $hasCapacity = ($activeReserved + $activeConfirmed) < $lockedSession->capacity;

            // Check if customer already created another active booking for this session in the meantime
            $hasAnotherActiveBooking = Booking::query()
                ->where('user_id', $lockedBooking->user_id)
                ->where('training_session_id', $lockedSession->id)
                ->where('id', '!=', $lockedBooking->id)
                ->whereIn('status', [
                    BookingStatus::PendingPayment->value,
                    BookingStatus::Confirmed->value,
                ])
                ->exists();

            if ($sessionOpenStatus && $beforeRegClose && $beforeStart && $hasCapacity && ! $hasAnotherActiveBooking) {
                // Restore expired booking -> confirmed
                $lockedBooking->update([
                    'status' => BookingStatus::Confirmed,
                    'confirmed_at' => $now,
                ]);

                Attendance::query()->firstOrCreate(
                    [
                        'training_session_id' => $lockedSession->id,
                        'booking_id' => $lockedBooking->id,
                    ],
                    [
                        'user_id' => $lockedBooking->user_id,
                        'attendance_status' => AttendanceStatus::Pending,
                        'review_status' => AttendanceReviewStatus::Draft,
                    ],
                );

                $this->recomputeSessionCountersLocked($lockedSession, $now);

                AuditLog::record(
                    event: 'booking.restored_from_late_payment',
                    auditableType: Booking::class,
                    auditableId: $lockedBooking->id,
                    oldValues: ['status' => BookingStatus::Expired->value],
                    newValues: ['status' => BookingStatus::Confirmed->value],
                    reason: 'Late Billplz payment received while session remained eligible and had available capacity.',
                );

                $restoredBooking = $lockedBooking->fresh(['user', 'trainingSession']);
                DB::afterCommit(function () use ($restoredBooking): void {
                    if (! $restoredBooking?->user || ! $restoredBooking->trainingSession) {
                        return;
                    }

                    $session = $restoredBooking->trainingSession;
                    app(EtmsNotificationService::class)->notifyUser(
                        user: $restoredBooking->user,
                        eventType: 'booking.confirmed',
                        title: "Late Payment Accepted — Booking Confirmed: {$session->title}",
                        message: "Your late payment was verified while seats remained available. Booking {$restoredBooking->booking_reference} is confirmed.",
                        actionUrl: route('portal.bookings.show', $restoredBooking, false),
                        actionLabel: 'View Booking Details',
                        details: [
                            'Booking Reference' => $restoredBooking->booking_reference,
                            'Session Code' => $session->session_code,
                        ],
                        reference: $restoredBooking->booking_reference,
                    );
                });

                return;
            }

            // Capacity unavailable or session closed/started/cancelled: retain expired booking and create system refund request
            $refund = Refund::query()->create([
                'refund_reference' => Refund::generateRefundReference(),
                'order_id' => $lockedOrder->id,
                'payment_id' => $lockedPayment->id,
                'booking_id' => $lockedBooking->id,
                'user_id' => $lockedOrder->user_id,
                'amount' => $lockedPayment->amount,
                'currency' => $lockedPayment->currency,
                'reason' => 'Late payment received after 15-minute reservation expired and session capacity or registration window was no longer available.',
                'status' => RefundStatus::Requested,
                'request_source' => 'system',
                'requested_by' => null,
                'source_event' => 'late_payment_capacity_unavailable',
                'requested_at' => $now,
            ]);

            AuditLog::record(
                event: 'refund.system_requested',
                auditableType: Refund::class,
                auditableId: $refund->id,
                oldValues: null,
                newValues: [
                    'refund_reference' => $refund->refund_reference,
                    'source_event' => 'late_payment_capacity_unavailable',
                    'amount' => $refund->amount,
                ],
                reason: $refund->reason,
            );

            $notifiedOrder = $lockedOrder->fresh(['user']);
            $refundRef = $refund->refund_reference;
            $refundAmt = number_format((float) $refund->amount, 2);

            DB::afterCommit(function () use ($notifiedOrder, $refundRef, $refundAmt): void {
                if (! $notifiedOrder?->user) {
                    return;
                }

                app(EtmsNotificationService::class)->notifyUser(
                    user: $notifiedOrder->user,
                    eventType: 'refund.requested',
                    title: "Refund Request Queued: {$refundRef}",
                    message: "Your late payment of MYR {$refundAmt} arrived after the 15-minute reservation expired and seats were no longer available. A refund request ({$refundRef}) has been queued for administrator processing.",
                    actionUrl: route('portal.orders.show', $notifiedOrder, false),
                    actionLabel: 'View Refund Status',
                    details: [
                        'Refund Reference' => $refundRef,
                        'Order Number' => $notifiedOrder->order_number,
                        'Amount' => "MYR {$refundAmt}",
                    ],
                    reference: $refundRef,
                );
            });
        }
    }

    /**
     * Fulfil an independent module purchase order by granting lifetime `ModuleEntitlement` (`D-006`, `D-018`).
     */
    protected function fulfilModuleOrderLocked(Order $lockedOrder, Carbon $now): void
    {
        foreach ($lockedOrder->items as $item) {
            if ($item->item_type === OrderItemType::SessionDocument && $item->session_material_id) {
                $this->fulfilSessionDocumentOrderItemLocked($lockedOrder, $item, $now);
                continue;
            }

            if ($item->item_type !== OrderItemType::Module || ! $item->module_id) {
                continue;
            }

            $existingActive = ModuleEntitlement::query()
                ->where('user_id', $lockedOrder->user_id)
                ->where('module_id', $item->module_id)
                ->where('status', EntitlementStatus::Active->value)
                ->lockForUpdate()
                ->first();

            if ($existingActive !== null) {
                continue;
            }

            $entitlement = ModuleEntitlement::query()->create([
                'user_id' => $lockedOrder->user_id,
                'module_id' => $item->module_id,
                'order_id' => $lockedOrder->id,
                'order_item_id' => $item->id,
                'grant_source' => 'purchase',
                'status' => EntitlementStatus::Active,
                'granted_at' => $now,
            ]);

            AuditLog::record(
                event: 'module_entitlement.granted_on_purchase',
                auditableType: ModuleEntitlement::class,
                auditableId: $entitlement->id,
                oldValues: null,
                newValues: [
                    'user_id' => $lockedOrder->user_id,
                    'module_id' => $item->module_id,
                    'order_id' => $lockedOrder->id,
                ],
                reason: 'Lifetime module entitlement granted upon verified Billplz payment.',
            );

            $freshEntitlement = $entitlement->fresh(['user', 'module']);
            $orderNumber = $lockedOrder->order_number;

            DB::afterCommit(function () use ($freshEntitlement, $orderNumber): void {
                if (! $freshEntitlement?->user || ! $freshEntitlement->module) {
                    return;
                }

                $module = $freshEntitlement->module;
                app(EtmsNotificationService::class)->notifyUser(
                    user: $freshEntitlement->user,
                    eventType: 'module.access_granted',
                    title: "Module Access Unlocked: {$module->title}",
                    message: "Your purchase ({$orderNumber}) is confirmed and you now have lifetime access to learning materials for {$module->title}.",
                    actionUrl: route('portal.modules.show', $module, false),
                    actionLabel: 'Access Learning Materials',
                    details: [
                        'Module' => "{$module->code} — {$module->title}",
                        'Order Number' => $orderNumber,
                    ],
                    reference: $module->code,
                );
            });
        }
    }

    protected function fulfilSessionDocumentOrderItemLocked(Order $lockedOrder, OrderItem $item, Carbon $now): void
    {
        $material = SessionMaterial::query()->with('trainingSession')->find($item->session_material_id);
        if (! $material) {
            throw new DomainException('The purchased session document no longer exists.');
        }

        $existingActive = SessionDocumentEntitlement::query()
            ->where('user_id', $lockedOrder->user_id)
            ->where('session_material_id', $material->id)
            ->where('status', EntitlementStatus::Active->value)
            ->lockForUpdate()
            ->first();

        if ($existingActive !== null) {
            return;
        }

        $entitlement = SessionDocumentEntitlement::query()->create([
            'user_id' => $lockedOrder->user_id,
            'training_session_id' => $material->training_session_id,
            'session_material_id' => $material->id,
            'order_id' => $lockedOrder->id,
            'order_item_id' => $item->id,
            'status' => EntitlementStatus::Active,
            'granted_at' => $now,
        ]);

        AuditLog::record(
            event: 'session_document_entitlement.granted_on_purchase',
            auditableType: SessionDocumentEntitlement::class,
            auditableId: $entitlement->id,
            oldValues: null,
            newValues: [
                'user_id' => $lockedOrder->user_id,
                'training_session_id' => $material->training_session_id,
                'session_material_id' => $material->id,
                'order_id' => $lockedOrder->id,
            ],
            reason: 'Session document entitlement granted upon verified Billplz payment.',
        );
    }

    /**
     * Recompute `reserved_seats_count`, `confirmed_seats_count`, and `status` on a locked session (`D-033`).
     */
    protected function recomputeSessionCountersLocked(TrainingSession $lockedSession, Carbon $now): void
    {
        if ($lockedSession->status === SessionStatus::Cancelled) {
            $lockedSession->reserved_seats_count = 0;
            $lockedSession->confirmed_seats_count = 0;
            $lockedSession->save();

            return;
        }

        $activeReserved = Booking::query()
            ->where('training_session_id', $lockedSession->id)
            ->where('status', BookingStatus::PendingPayment->value)
            ->where('reservation_expires_at', '>', $now)
            ->count();

        $activeConfirmed = Booking::query()
            ->where('training_session_id', $lockedSession->id)
            ->where('status', BookingStatus::Confirmed->value)
            ->count();

        $lockedSession->reserved_seats_count = min($lockedSession->capacity, $activeReserved);
        $lockedSession->confirmed_seats_count = min(
            max(0, $lockedSession->capacity - $lockedSession->reserved_seats_count),
            $activeConfirmed
        );

        $occupied = $lockedSession->reserved_seats_count + $lockedSession->confirmed_seats_count;

        if (in_array($lockedSession->status, [SessionStatus::Published, SessionStatus::Full, SessionStatus::RegistrationClosed], true)) {
            if ($occupied >= $lockedSession->capacity) {
                $lockedSession->status = SessionStatus::Full;
            } elseif ($lockedSession->registration_closes_at !== null && $now->gte($lockedSession->registration_closes_at)) {
                $lockedSession->status = SessionStatus::RegistrationClosed;
            } else {
                $lockedSession->status = SessionStatus::Published;
            }
        }

        $lockedSession->save();
    }
}

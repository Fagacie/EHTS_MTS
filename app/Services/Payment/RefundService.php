<?php

namespace App\Services\Payment;

use App\Enums\AttendanceReviewStatus;
use App\Enums\BookingStatus;
use App\Enums\EntitlementStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Enums\RefundMethod;
use App\Enums\RefundStatus;
use App\Enums\SessionStatus;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\ModuleEntitlement;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\SessionDocumentEntitlement;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Notification\EtmsNotificationService;
use DomainException;
use RuntimeException;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(
        protected PaystackGatewayService $paystack,
    ) {}

    /**
     * Initiate, approve, and complete an administrator-controlled full or partial refund (`D-021`, Section 5.8 & 5.9).
     */
    public function recordAndCompleteAdminRefund(
        Payment $payment,
        User $admin,
        float $amount,
        RefundMethod|string $method,
        string $reason,
        ?string $disbursementReference = null,
    ): Refund {
        $normalizedAmount = round($amount, 2);
        if ($normalizedAmount <= 0) {
            throw new DomainException(sprintf('Refund amount must be greater than %s 0.00.', $payment->currency ?: 'NGN'));
        }

        $methodValue = $method instanceof RefundMethod ? $method->value : $method;
        if (RefundMethod::tryFrom($methodValue) === null) {
            throw new DomainException('A valid refund disbursement method is required.');
        }

        $payment->loadMissing(['order.bookings', 'order.items']);
        if (Refund::query()
            ->where('payment_id', $payment->id)
            ->where('amount', $normalizedAmount)
            ->whereIn('status', [RefundStatus::Processing, RefundStatus::Completed])
            ->exists()) {
            throw new DomainException('A refund for this amount is already processing or completed.');
        }
        $prelimOrder = $payment->order;
        $sessionId = $prelimOrder->bookings->first()?->training_session_id
            ?? $prelimOrder->items->first()?->training_session_id;

        return DB::transaction(function () use (
            $payment,
            $prelimOrder,
            $sessionId,
            $admin,
            $normalizedAmount,
            $methodValue,
            $reason,
            $disbursementReference,
        ): Refund {
            // 1. Lock training_sessions first if session_booking
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
                ->whereKey($prelimOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 3. Lock payments
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedPayment->status, [PaymentStatus::Successful, PaymentStatus::PartiallyRefunded], true)) {
                throw new DomainException('Only successful or partially refunded payments can be refunded.');
            }

            $refundable = $lockedPayment->refundableBalance();
            if ($normalizedAmount > $refundable) {
                throw new DomainException(sprintf(
                    'Refund amount (%s %s) exceeds remaining refundable balance (%s %s).',
                    $lockedPayment->currency ?: 'NGN',
                    number_format($normalizedAmount, 2),
                    $lockedPayment->currency ?: 'NGN',
                    number_format($refundable, 2),
                ));
            }

            if (Refund::query()
                ->where('payment_id', $lockedPayment->id)
                ->where('amount', $normalizedAmount)
                ->whereIn('status', [RefundStatus::Processing, RefundStatus::Completed])
                ->exists()) {
                throw new DomainException('A refund for this amount is already processing or completed.');
            }

            $providerRefund = null;
            $providerCompleted = true;
            if ($lockedPayment->gateway === 'paystack') {
                $providerRefund = $this->paystack->refundTransaction(
                    $lockedPayment,
                    PaystackGatewayService::toMinorUnits((string) $normalizedAmount, (string) $lockedPayment->currency),
                );
                $providerStatus = strtolower((string) ($providerRefund['status'] ?? ''));
                if (! in_array($providerStatus, ['processed', 'success', 'completed', 'pending'], true)) {
                    throw new RuntimeException('Paystack rejected the refund request: '.$providerStatus);
                }
                $providerCompleted = in_array($providerStatus, ['processed', 'success', 'completed'], true);
            }

            // 4. Lock booking (if session_booking)
            $lockedBooking = Booking::query()
                ->where('order_id', $lockedOrder->id)
                ->lockForUpdate()
                ->first();

            $now = now();

            $refund = Refund::query()->create([
                'refund_reference' => Refund::generateRefundReference(),
                'order_id' => $lockedOrder->id,
                'payment_id' => $lockedPayment->id,
                'booking_id' => $lockedBooking?->id,
                'user_id' => $lockedOrder->user_id,
                'amount' => $normalizedAmount,
                'currency' => $lockedPayment->currency ?: 'MYR',
                'refund_method' => $methodValue,
                'disbursement_reference' => $disbursementReference,
                'disbursement_status' => $providerCompleted ? 'completed' : 'processing',
                'provider_disbursement_id' => isset($providerRefund['id']) ? (string) $providerRefund['id'] : null,
                'disbursement_payload' => $providerRefund,
                'reason' => $reason,
                'status' => $providerCompleted ? RefundStatus::Completed : RefundStatus::Processing,
                'request_source' => 'administrator',
                'requested_by' => $admin->id,
                'source_event' => 'admin_refund',
                'gateway_payload' => $providerRefund,
                'approved_by' => $admin->id,
                'processed_by' => $providerCompleted ? $admin->id : null,
                'requested_at' => $now,
                'approved_at' => $now,
                'processed_at' => $providerCompleted ? $now : null,
            ]);

            if ($providerCompleted) {
                $this->applyCompletedRefundEffectsLocked(
                    lockedSession: $lockedSession,
                    lockedOrder: $lockedOrder,
                    lockedPayment: $lockedPayment,
                    lockedBooking: $lockedBooking,
                    refund: $refund,
                    admin: $admin,
                );
            }

            return $refund->fresh(['order', 'payment', 'booking']);
        });
    }

    /**
     * Approve and complete an existing `requested` / `approved` / `processing` refund record.
     */
    public function approveAndCompleteRequestedRefund(
        Refund $refund,
        User $admin,
        RefundMethod|string $method,
        ?string $disbursementReference = null,
    ): Refund {
        $methodValue = $method instanceof RefundMethod ? $method->value : $method;
        if (RefundMethod::tryFrom($methodValue) === null) {
            throw new DomainException('A valid refund disbursement method is required.');
        }

        $refund->loadMissing(['order.bookings', 'order.items']);
        $prelimOrder = $refund->order;
        $sessionId = $prelimOrder->bookings->first()?->training_session_id
            ?? $prelimOrder->items->first()?->training_session_id;

        return DB::transaction(function () use (
            $refund,
            $prelimOrder,
            $sessionId,
            $admin,
            $methodValue,
            $disbursementReference,
        ): Refund {
            $lockedSession = null;
            if ($sessionId !== null) {
                $lockedSession = TrainingSession::query()
                    ->whereKey($sessionId)
                    ->lockForUpdate()
                    ->first();
            }

            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->whereKey($prelimOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::query()
                ->whereKey($refund->payment_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedBooking = Booking::query()
                ->where('order_id', $lockedOrder->id)
                ->lockForUpdate()
                ->first();

            /** @var Refund $lockedRefund */
            $lockedRefund = Refund::query()
                ->whereKey($refund->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRefund->status === RefundStatus::Completed) {
                return $lockedRefund;
            }

            if ((float) $lockedRefund->amount > $lockedPayment->refundableBalance()) {
                throw new DomainException('Refund amount exceeds the remaining refundable balance on this payment.');
            }

            $now = now();

            $lockedRefund->update([
                'refund_method' => $methodValue,
                'disbursement_reference' => $disbursementReference ?? $lockedRefund->disbursement_reference,
                'disbursement_status' => 'completed',
                'status' => RefundStatus::Completed,
                'approved_by' => $lockedRefund->approved_by ?? $admin->id,
                'approved_at' => $lockedRefund->approved_at ?? $now,
                'processed_by' => $providerCompleted ? $admin->id : null,
                'processed_at' => $providerCompleted ? $now : null,
            ]);

            $this->applyCompletedRefundEffectsLocked(
                lockedSession: $lockedSession,
                lockedOrder: $lockedOrder,
                lockedPayment: $lockedPayment,
                lockedBooking: $lockedBooking,
                refund: $lockedRefund,
                admin: $admin,
            );

            return $lockedRefund->fresh(['order', 'payment', 'booking']);
        });
    }

    /**
     * Reject a requested refund record.
     */
    public function rejectRefund(Refund $refund, User $admin, string $failureReason): Refund
    {
        return DB::transaction(function () use ($refund, $admin, $failureReason): Refund {
            /** @var Refund $lockedRefund */
            $lockedRefund = Refund::query()
                ->whereKey($refund->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRefund->status === RefundStatus::Completed) {
                throw new DomainException('Completed refunds cannot be rejected.');
            }

            $oldStatus = $lockedRefund->status->value;

            $lockedRefund->update([
                'status' => RefundStatus::Rejected,
                'failure_reason' => $failureReason,
            ]);

            AuditLog::record(
                event: 'refund.rejected',
                auditableType: Refund::class,
                auditableId: $lockedRefund->id,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => RefundStatus::Rejected->value],
                reason: $failureReason,
                actor: $admin,
            );

            return $lockedRefund->fresh();
        });
    }

    /**
     * Synchronize `payments`, `orders`, `bookings` / `module_entitlements`, and `training_sessions` after a refund completes.
     */
    protected function applyCompletedRefundEffectsLocked(
        ?TrainingSession $lockedSession,
        Order $lockedOrder,
        Payment $lockedPayment,
        ?Booking $lockedBooking,
        Refund $refund,
        User $admin,
    ): void {
        $now = now();
        $newRefundedTotal = round(((float) $lockedPayment->refunded_amount) + ((float) $refund->amount), 2);
        $isFullRefund = $newRefundedTotal >= round((float) $lockedPayment->amount, 2);

        $newPaymentStatus = $isFullRefund ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded;
        $newOrderStatus = $isFullRefund ? OrderStatus::Refunded : OrderStatus::PartiallyRefunded;

        $lockedPayment->update([
            'refunded_amount' => min((float) $lockedPayment->amount, $newRefundedTotal),
            'status' => $newPaymentStatus,
        ]);

        $lockedOrder->update([
            'status' => $newOrderStatus,
            'refunded_at' => $now,
        ]);

        // Update downstream booking or module entitlement on full refund (Section 5.9)
        if ($isFullRefund) {
            if ($lockedOrder->order_type === OrderType::SessionBooking && $lockedBooking !== null) {
                $lockedBooking->update([
                    'status' => BookingStatus::Refunded,
                    'refunded_at' => $now,
                ]);

                if ($lockedBooking->attendance && $lockedBooking->attendance->review_status !== AttendanceReviewStatus::Verified) {
                    $lockedBooking->attendance->delete();
                }

                if ($lockedSession !== null) {
                    $this->recomputeSessionCountersLocked($lockedSession);
                }
            } elseif ($lockedOrder->order_type === OrderType::ModulePurchase) {
                $entitlements = ModuleEntitlement::query()
                    ->where('order_id', $lockedOrder->id)
                    ->where('status', EntitlementStatus::Active->value)
                    ->lockForUpdate()
                    ->get();

                foreach ($entitlements as $entitlement) {
                    $entitlement->update([
                        'status' => EntitlementStatus::Revoked,
                        'revoked_at' => $now,
                        'revoked_by' => $admin->id,
                        'revocation_reason' => "Order refunded ({$refund->refund_reference}): {$refund->reason}",
                    ]);

                    AuditLog::record(
                        event: 'module_entitlement.revoked_on_refund',
                        auditableType: ModuleEntitlement::class,
                        auditableId: $entitlement->id,
                        oldValues: ['status' => EntitlementStatus::Active->value],
                        newValues: ['status' => EntitlementStatus::Revoked->value],
                        reason: $refund->reason,
                        actor: $admin,
                    );
                }

                $documentEntitlements = SessionDocumentEntitlement::query()
                    ->where('order_id', $lockedOrder->id)
                    ->where('status', EntitlementStatus::Active->value)
                    ->lockForUpdate()
                    ->get();

                foreach ($documentEntitlements as $entitlement) {
                    $entitlement->update([
                        'status' => EntitlementStatus::Revoked,
                        'revoked_at' => $now,
                        'revoked_by' => $admin->id,
                        'revocation_reason' => "Order refunded ({$refund->refund_reference}): {$refund->reason}",
                    ]);

                    AuditLog::record(
                        event: 'session_document_entitlement.revoked_on_refund',
                        auditableType: SessionDocumentEntitlement::class,
                        auditableId: $entitlement->id,
                        oldValues: ['status' => EntitlementStatus::Active->value],
                        newValues: ['status' => EntitlementStatus::Revoked->value],
                        reason: $refund->reason,
                        actor: $admin,
                    );
                }
            }
        }

        AuditLog::record(
            event: 'refund.completed',
            auditableType: Refund::class,
            auditableId: $refund->id,
            oldValues: null,
            newValues: [
                'refund_reference' => $refund->refund_reference,
                'amount' => $refund->amount,
                'refund_method' => $refund->refund_method?->value,
                'payment_status' => $newPaymentStatus->value,
                'order_status' => $newOrderStatus->value,
            ],
            reason: $refund->reason,
            actor: $admin,
        );

        $orderForNotification = $lockedOrder->fresh(['user']);
        $refundAmount = number_format((float) $refund->amount, 2);
        $refundReference = $refund->refund_reference;
        $refundReason = $refund->reason;

        DB::afterCommit(function () use ($orderForNotification, $refundAmount, $refundReference, $refundReason): void {
            if (! $orderForNotification?->user) {
                return;
            }

            app(EtmsNotificationService::class)->notifyUser(
                user: $orderForNotification->user,
                eventType: 'refund.completed',
                title: "Refund Completed: {$refundReference}",
                message: "Your refund of RM {$refundAmount} for order {$orderForNotification->order_number} has been processed.",
                actionUrl: route('portal.orders.show', $orderForNotification, false),
                actionLabel: 'View Order & Refund Status',
                details: [
                    'Refund Reference' => $refundReference,
                    'Order Number' => $orderForNotification->order_number,
                    'Refund Amount' => "MYR {$refundAmount}",
                    'Reason' => $refundReason,
                ],
                reference: $refundReference,
            );
        });
    }

    protected function recomputeSessionCountersLocked(TrainingSession $lockedSession): void
    {
        if ($lockedSession->status === SessionStatus::Cancelled) {
            $lockedSession->reserved_seats_count = 0;
            $lockedSession->confirmed_seats_count = 0;
            $lockedSession->save();

            return;
        }

        $now = now();

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

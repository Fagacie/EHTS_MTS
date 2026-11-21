<?php

namespace App\Services\Booking;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\BookingStatus;
use App\Enums\OrderItemType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\SessionStatus;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\SystemSetting;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Notification\EtmsNotificationService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SessionBookingService
{
    /**
     * Reserve a single seat in a physical training session for 15 minutes (D-019, D-033, Section 3.6).
     *
     * @param  array{
     *     customer_type?: string,
     *     phone?: string|null,
     *     company_name?: string|null,
     *     billing_address?: array<string, mixed>|null
     * }  $billingInput
     */
    public function reserveSeat(User $user, TrainingSession $session, array $billingInput = []): Booking
    {
        $result = DB::transaction(function () use ($user, $session, $billingInput): array {
            /** @var TrainingSession $lockedSession */
            $lockedSession = TrainingSession::query()
                ->with('module')
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->sweepExpiredHoldsLocked($lockedSession);

            // Check if the customer already has an active booking for this session
            $existingActive = Booking::query()
                ->where('user_id', $user->id)
                ->where('training_session_id', $lockedSession->id)
                ->whereIn('status', [
                    BookingStatus::PendingPayment->value,
                    BookingStatus::Confirmed->value,
                ])
                ->first();

            if ($existingActive !== null) {
                if ($existingActive->status === BookingStatus::Confirmed) {
                    $this->syncSessionCountersLocked($lockedSession);

                    return [
                        'ok' => false,
                        'error' => 'You already hold a confirmed seat for this training session.',
                    ];
                }

                if ($existingActive->isHoldActive()) {
                    $this->syncSessionCountersLocked($lockedSession);

                    return [
                        'ok' => true,
                        'booking' => $existingActive->fresh(['order', 'orderItem', 'trainingSession.module']),
                    ];
                }
            }

            $now = now();

            // Validate session publication & lifecycle status
            if (! in_array($lockedSession->status, [SessionStatus::Published, SessionStatus::Full], true)) {
                $this->syncSessionCountersLocked($lockedSession);

                return [
                    'ok' => false,
                    'error' => 'This training session is not currently open for registration.',
                ];
            }

            if ($lockedSession->registration_opens_at !== null && $now->lt($lockedSession->registration_opens_at)) {
                $this->syncSessionCountersLocked($lockedSession);

                return [
                    'ok' => false,
                    'error' => 'Registration for this training session has not opened yet.',
                ];
            }

            if ($lockedSession->registration_closes_at !== null && $now->gte($lockedSession->registration_closes_at)) {
                if ($lockedSession->status === SessionStatus::Published) {
                    $lockedSession->status = SessionStatus::RegistrationClosed;
                    $lockedSession->save();
                }

                return [
                    'ok' => false,
                    'error' => 'Registration for this training session has closed.',
                ];
            }

            if ($lockedSession->starts_at !== null && $now->gte($lockedSession->starts_at)) {
                return [
                    'ok' => false,
                    'error' => 'This training session has already started.',
                ];
            }

            // Recompute authoritative seat counts from active bookings (D-033)
            [$activeReserved, $activeConfirmed] = $this->computeActiveCountsLocked($lockedSession->id);

            if (($activeReserved + $activeConfirmed) >= $lockedSession->capacity) {
                $lockedSession->reserved_seats_count = $activeReserved;
                $lockedSession->confirmed_seats_count = $activeConfirmed;
                $lockedSession->status = SessionStatus::Full;
                $lockedSession->save();

                return [
                    'ok' => false,
                    'error' => 'This training session is currently full.',
                ];
            }

            $holdMinutes = (int) SystemSetting::getValue('booking.reservation_hold_minutes', 15);
            if ($holdMinutes <= 0) {
                $holdMinutes = 15;
            }

            $expiresAt = $now->copy()->addMinutes($holdMinutes);
            $profile = $user->customerProfile;

            $customerType = 'individual';
            $companyName = null;

            $phone = $billingInput['phone'] ?? $profile?->phone;
            $addr = $billingInput['billing_address'] ?? [];

            $unitPrice = (float) $lockedSession->price;

            $order = Order::query()->create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user->id,
                'order_type' => OrderType::SessionBooking,
                'status' => OrderStatus::PendingPayment,
                'subtotal_amount' => $unitPrice,
                'total_amount' => $unitPrice,
                'currency' => $lockedSession->currency ?: 'MYR',
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
                'expires_at' => $expiresAt,
            ]);

            $orderItem = OrderItem::query()->create([
                'order_id' => $order->id,
                'order_type' => OrderType::SessionBooking,
                'item_type' => OrderItemType::TrainingSession,
                'training_session_id' => $lockedSession->id,
                'module_id' => null,
                'item_code_snapshot' => $lockedSession->session_code,
                'item_title_snapshot' => $lockedSession->title,
                'unit_price' => $unitPrice,
                'quantity' => 1,
                'line_total' => $unitPrice,
                'metadata_snapshot' => [
                    'module_code' => $lockedSession->module?->code,
                    'module_title' => $lockedSession->module?->title,
                    'starts_at' => $lockedSession->starts_at?->toIso8601String(),
                    'ends_at' => $lockedSession->ends_at?->toIso8601String(),
                    'venue_name' => $lockedSession->venue_name,
                    'venue_city' => $lockedSession->venue_city,
                ],
            ]);

            $booking = Booking::query()->create([
                'booking_reference' => Booking::generateBookingReference(),
                'user_id' => $user->id,
                'training_session_id' => $lockedSession->id,
                'order_id' => $order->id,
                'order_item_id' => $orderItem->id,
                'status' => BookingStatus::PendingPayment,
                'reserved_at' => $now,
                'reservation_expires_at' => $expiresAt,
            ]);

            $this->syncSessionCountersLocked($lockedSession);

            AuditLog::record(
                event: 'booking.reserved',
                auditableType: Booking::class,
                auditableId: $booking->id,
                oldValues: null,
                newValues: [
                    'booking_reference' => $booking->booking_reference,
                    'training_session_id' => $lockedSession->id,
                    'session_code' => $lockedSession->session_code,
                    'order_number' => $order->order_number,
                    'reservation_expires_at' => $expiresAt->toIso8601String(),
                ],
                reason: '15-minute physical session seat reservation created.',
                actor: $user,
            );

            return [
                'ok' => true,
                'booking' => $booking->fresh(['order', 'orderItem', 'trainingSession.module']),
            ];
        });

        if (! $result['ok']) {
            throw new DomainException($result['error']);
        }

        return $result['booking'];
    }

    /**
     * Confirm a booking (used by Stage 5 administrative confirmation / seeding and Stage 6 payment webhook).
     */
    public function confirmBooking(Booking $booking, ?User $actor = null, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $reason): Booking {
            /** @var TrainingSession $lockedSession */
            $lockedSession = TrainingSession::query()
                ->whereKey($booking->training_session_id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::query()
                ->with('order')
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBooking->status === BookingStatus::Confirmed) {
                return $lockedBooking;
            }

            if (! in_array($lockedBooking->status, [BookingStatus::PendingPayment, BookingStatus::Expired], true)) {
                throw new DomainException('Only pending or expired bookings can be confirmed.');
            }

            $successfulPayment = Payment::query()
                ->where('order_id', $lockedBooking->order_id)
                ->where('status', PaymentStatus::Successful->value)
                ->exists();

            if ($lockedBooking->order?->status !== OrderStatus::Paid || ! $successfulPayment) {
                throw new DomainException('A booking can only be confirmed after verified Billplz payment.');
            }

            [$activeReserved, $activeConfirmed] = $this->computeActiveCountsLocked($lockedSession->id);
            $alreadyCountedInReserved = $lockedBooking->status === BookingStatus::PendingPayment;
            $projectedOccupied = $activeConfirmed + ($alreadyCountedInReserved ? $activeReserved : $activeReserved + 1);

            if ($projectedOccupied > $lockedSession->capacity) {
                throw new DomainException('Cannot confirm booking because session capacity has already been reached.');
            }

            $oldStatus = $lockedBooking->status->value;
            $now = now();

            $lockedBooking->update([
                'status' => BookingStatus::Confirmed,
                'confirmed_at' => $now,
            ]);

            if ($lockedBooking->order && $lockedBooking->order->status !== OrderStatus::Paid) {
                $lockedBooking->order->update([
                    'status' => OrderStatus::Paid,
                    'paid_at' => $now,
                ]);
            }

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

            $this->syncSessionCountersLocked($lockedSession);

            AuditLog::record(
                event: 'booking.confirmed',
                auditableType: Booking::class,
                auditableId: $lockedBooking->id,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => BookingStatus::Confirmed->value,
                    'confirmed_at' => $now->toIso8601String(),
                ],
                reason: $reason ?? 'Booking confirmed and seat allocated.',
                actor: $actor,
            );

            $confirmedBooking = $lockedBooking->fresh(['order', 'trainingSession', 'user', 'attendance']);

            DB::afterCommit(function () use ($confirmedBooking): void {
                if (! $confirmedBooking?->user || ! $confirmedBooking->trainingSession) {
                    return;
                }

                $session = $confirmedBooking->trainingSession;

                app(EtmsNotificationService::class)->notifyUser(
                    user: $confirmedBooking->user,
                    eventType: 'booking.confirmed',
                    title: "Booking Confirmed: {$session->title}",
                    message: "Your seat for {$session->title} ({$session->session_code}) has been confirmed.",
                    actionUrl: route('portal.bookings.show', $confirmedBooking, false),
                    actionLabel: 'View Booking Details',
                    details: [
                        'Booking Reference' => $confirmedBooking->booking_reference,
                        'Session Code' => $session->session_code,
                        'Start (MYT)' => $session->starts_at?->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))->format('d M Y, h:i A') ?? 'TBA',
                        'Venue' => trim("{$session->venue_name}, {$session->venue_city}", ', '),
                    ],
                    reference: $confirmedBooking->booking_reference,
                );
            });

            return $confirmedBooking;
        });
    }

    /**
     * Cancel an individual booking by an administrator.
     */
    public function cancelBookingByAdmin(Booking $booking, User $admin, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $admin, $reason): Booking {
            /** @var TrainingSession $lockedSession */
            $lockedSession = TrainingSession::query()
                ->whereKey($booking->training_session_id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::query()
                ->with(['order', 'attendance'])
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBooking->status === BookingStatus::Cancelled) {
                return $lockedBooking;
            }

            $oldStatus = $lockedBooking->status->value;
            $now = now();

            $lockedBooking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => $now,
                'cancelled_by' => $admin->id,
                'cancellation_reason' => $reason,
            ]);

            if ($lockedBooking->order && $lockedBooking->order->status === OrderStatus::PendingPayment) {
                $lockedBooking->order->update([
                    'status' => OrderStatus::Cancelled,
                    'cancelled_at' => $now,
                ]);

                if (Schema::hasTable('payments')) {
                    Payment::query()
                        ->where('order_id', $lockedBooking->order_id)
                        ->where('status', PaymentStatus::Pending->value)
                        ->update([
                            'status' => PaymentStatus::Cancelled->value,
                            'cancelled_at' => $now,
                            'updated_at' => $now,
                        ]);
                }
            }

            if ($lockedBooking->attendance && $lockedBooking->attendance->review_status !== AttendanceReviewStatus::Verified) {
                $lockedBooking->attendance->delete();
            }

            $this->syncSessionCountersLocked($lockedSession);

            AuditLog::record(
                event: 'booking.cancelled',
                auditableType: Booking::class,
                auditableId: $lockedBooking->id,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => BookingStatus::Cancelled->value,
                    'cancelled_at' => $now->toIso8601String(),
                ],
                reason: $reason,
                actor: $admin,
            );

            $cancelledBooking = $lockedBooking->fresh(['order', 'trainingSession', 'user']);

            DB::afterCommit(function () use ($cancelledBooking, $reason): void {
                if (! $cancelledBooking?->user || ! $cancelledBooking->trainingSession) {
                    return;
                }

                $session = $cancelledBooking->trainingSession;

                app(EtmsNotificationService::class)->notifyUser(
                    user: $cancelledBooking->user,
                    eventType: 'booking.cancelled',
                    title: "Booking Cancelled: {$session->title}",
                    message: "Your booking {$cancelledBooking->booking_reference} for {$session->title} has been cancelled.",
                    actionUrl: route('portal.bookings.show', $cancelledBooking, false),
                    actionLabel: 'View Booking Details',
                    details: [
                        'Booking Reference' => $cancelledBooking->booking_reference,
                        'Session' => "{$session->session_code} — {$session->title}",
                        'Reason' => $reason,
                    ],
                    reference: $cancelledBooking->booking_reference,
                );
            });

            return $cancelledBooking;
        });
    }

    /**
     * Cancel an entire training session and bulk-cancel active bookings (BR-16, D-021).
     */
    public function cancelSessionByAdmin(TrainingSession $session, User $admin, string $reason): TrainingSession
    {
        return DB::transaction(function () use ($session, $admin, $reason): TrainingSession {
            /** @var TrainingSession $lockedSession */
            $lockedSession = TrainingSession::query()
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSession->status === SessionStatus::Completed) {
                throw new DomainException('Completed training sessions cannot be cancelled.');
            }

            $oldStatus = $lockedSession->status->value;
            $now = now();

            $activeBookings = Booking::query()
                ->with('order')
                ->where('training_session_id', $lockedSession->id)
                ->whereIn('status', [
                    BookingStatus::PendingPayment->value,
                    BookingStatus::Confirmed->value,
                ])
                ->lockForUpdate()
                ->get();

            foreach ($activeBookings as $booking) {
                $wasConfirmed = $booking->status === BookingStatus::Confirmed;

                $booking->update([
                    'status' => BookingStatus::Cancelled,
                    'cancelled_at' => $now,
                    'cancelled_by' => $admin->id,
                    'cancellation_reason' => 'Session cancelled by administrator: '.$reason,
                ]);

                if ($booking->order && $booking->order->status === OrderStatus::PendingPayment) {
                    $booking->order->update([
                        'status' => OrderStatus::Cancelled,
                        'cancelled_at' => $now,
                    ]);

                    if (Schema::hasTable('payments')) {
                        Payment::query()
                            ->where('order_id', $booking->order_id)
                            ->where('status', PaymentStatus::Pending->value)
                            ->update([
                                'status' => PaymentStatus::Cancelled->value,
                                'cancelled_at' => $now,
                                'updated_at' => $now,
                            ]);
                    }
                }

                if ($wasConfirmed && Schema::hasTable('payments') && Schema::hasTable('refunds')) {
                    $successfulPayment = Payment::query()
                        ->where('order_id', $booking->order_id)
                        ->whereIn('status', [PaymentStatus::Successful->value, PaymentStatus::PartiallyRefunded->value])
                        ->first();

                    if ($successfulPayment && $successfulPayment->refundableBalance() > 0) {
                        $existingRefund = Refund::query()
                            ->where('order_id', $booking->order_id)
                            ->where('payment_id', $successfulPayment->id)
                            ->whereIn('status', [
                                RefundStatus::Requested->value,
                                RefundStatus::Approved->value,
                                RefundStatus::Processing->value,
                                RefundStatus::Completed->value,
                            ])
                            ->exists();

                        if (! $existingRefund) {
                            Refund::query()->create([
                                'refund_reference' => Refund::generateRefundReference(),
                                'order_id' => $booking->order_id,
                                'payment_id' => $successfulPayment->id,
                                'booking_id' => $booking->id,
                                'user_id' => $booking->user_id,
                                'amount' => $successfulPayment->refundableBalance(),
                                'currency' => $successfulPayment->currency ?: 'MYR',
                                'reason' => 'Session cancelled by administrator: '.$reason,
                                'status' => RefundStatus::Requested,
                                'request_source' => 'system',
                                'requested_by' => null,
                                'source_event' => 'session_cancelled',
                                'requested_at' => $now,
                            ]);
                        }
                    }
                }
            }

            $lockedSession->update([
                'status' => SessionStatus::Cancelled,
                'cancelled_at' => $now,
                'cancelled_by' => $admin->id,
                'cancellation_reason' => $reason,
                'reserved_seats_count' => 0,
                'confirmed_seats_count' => 0,
                'updated_by' => $admin->id,
            ]);

            AuditLog::record(
                event: 'session.cancelled',
                auditableType: TrainingSession::class,
                auditableId: $lockedSession->id,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => SessionStatus::Cancelled->value,
                    'cancelled_bookings_count' => $activeBookings->count(),
                ],
                reason: $reason,
                actor: $admin,
            );

            $freshSession = $lockedSession->fresh();
            $cancelledBookings = $activeBookings->loadMissing(['user']);
            $assignedInstructors = $lockedSession->instructors()->get();

            DB::afterCommit(function () use ($freshSession, $cancelledBookings, $assignedInstructors, $reason): void {
                $notifier = app(EtmsNotificationService::class);

                foreach ($cancelledBookings as $cancelledBooking) {
                    if (! $cancelledBooking->user) {
                        continue;
                    }

                    $notifier->notifyUser(
                        user: $cancelledBooking->user,
                        eventType: 'session.cancelled',
                        title: "Session Cancelled: {$freshSession->title}",
                        message: "The physical training session {$freshSession->title} ({$freshSession->session_code}) has been cancelled. Your booking {$cancelledBooking->booking_reference} has been released and eligible paid orders have a refund request queued.",
                        actionUrl: route('portal.bookings.show', $cancelledBooking, false),
                        actionLabel: 'View Booking Status',
                        details: [
                            'Booking Reference' => $cancelledBooking->booking_reference,
                            'Session Code' => $freshSession->session_code,
                            'Cancellation Reason' => $reason,
                        ],
                        reference: $cancelledBooking->booking_reference,
                    );
                }

                foreach ($assignedInstructors as $instructor) {
                    $notifier->notifyUser(
                        user: $instructor,
                        eventType: 'session.cancelled',
                        title: "Assigned Session Cancelled: {$freshSession->title}",
                        message: "The training session {$freshSession->session_code} ({$freshSession->title}) has been cancelled by an administrator.",
                        actionUrl: route('instructor.sessions.show', $freshSession, false),
                        actionLabel: 'View Session Details',
                        details: [
                            'Session Code' => $freshSession->session_code,
                            'Reason' => $reason,
                        ],
                        reference: $freshSession->session_code,
                    );
                }
            });

            return $freshSession;
        });
    }

    /**
     * Sweep expired reservations and reconcile seat counters for a single session (D-019, D-033).
     */
    public function reconcileSessionCounters(int $sessionId, ?string $reason = null): TrainingSession
    {
        return DB::transaction(function () use ($sessionId, $reason): TrainingSession {
            /** @var TrainingSession $lockedSession */
            $lockedSession = TrainingSession::query()
                ->whereKey($sessionId)
                ->lockForUpdate()
                ->firstOrFail();

            $sweptCount = $this->sweepExpiredHoldsLocked($lockedSession);

            $oldReserved = (int) $lockedSession->reserved_seats_count;
            $oldConfirmed = (int) $lockedSession->confirmed_seats_count;
            $oldStatus = $lockedSession->status->value;

            $this->syncSessionCountersLocked($lockedSession);

            $driftDetected = ($oldReserved !== (int) $lockedSession->reserved_seats_count)
                || ($oldConfirmed !== (int) $lockedSession->confirmed_seats_count)
                || ($oldStatus !== $lockedSession->status->value);

            if ($driftDetected || $sweptCount > 0) {
                AuditLog::record(
                    event: 'session.capacity_reconciled',
                    auditableType: TrainingSession::class,
                    auditableId: $lockedSession->id,
                    oldValues: [
                        'reserved_seats_count' => $oldReserved,
                        'confirmed_seats_count' => $oldConfirmed,
                        'status' => $oldStatus,
                    ],
                    newValues: [
                        'reserved_seats_count' => $lockedSession->reserved_seats_count,
                        'confirmed_seats_count' => $lockedSession->confirmed_seats_count,
                        'status' => $lockedSession->status->value,
                        'swept_expired_holds' => $sweptCount,
                    ],
                    reason: $reason ?? 'Automated seat reservation and counter reconciliation.',
                );
            }

            return $lockedSession;
        });
    }

    /**
     * Sweep expired holds and reconcile counters across all active sessions.
     *
     * @return array{sessions_checked: int, holds_expired: int}
     */
    public function reconcileAllActiveSessions(): array
    {
        $sessionIds = TrainingSession::query()
            ->whereIn('status', [
                SessionStatus::Published->value,
                SessionStatus::Full->value,
                SessionStatus::RegistrationClosed->value,
                SessionStatus::InProgress->value,
            ])
            ->orWhereHas('bookings', fn ($q) => $q->where('status', BookingStatus::PendingPayment->value))
            ->pluck('id');

        $expiredBefore = Booking::query()
            ->where('status', BookingStatus::PendingPayment->value)
            ->where('reservation_expires_at', '<=', now())
            ->count();

        foreach ($sessionIds as $sessionId) {
            $this->reconcileSessionCounters((int) $sessionId);
        }

        return [
            'sessions_checked' => $sessionIds->count(),
            'holds_expired' => $expiredBefore,
        ];
    }

    /**
     * Sweep expired `pending_payment` bookings for a locked session.
     */
    protected function sweepExpiredHoldsLocked(TrainingSession $lockedSession): int
    {
        $now = now();

        $expiredBookings = Booking::query()
            ->with('user')
            ->where('training_session_id', $lockedSession->id)
            ->where('status', BookingStatus::PendingPayment->value)
            ->where('reservation_expires_at', '<=', $now)
            ->lockForUpdate()
            ->get();

        if ($expiredBookings->isEmpty()) {
            return 0;
        }

        $orderIds = $expiredBookings->pluck('order_id')->filter()->unique()->values()->all();

        Booking::query()
            ->whereIn('id', $expiredBookings->pluck('id')->all())
            ->update([
                'status' => BookingStatus::Expired->value,
                'expired_at' => $now,
                'updated_at' => $now,
            ]);

        if (! empty($orderIds)) {
            Order::query()
                ->whereIn('id', $orderIds)
                ->where('status', OrderStatus::PendingPayment->value)
                ->update([
                    'status' => OrderStatus::Expired->value,
                    'updated_at' => $now,
                ]);

            if (Schema::hasTable('payments')) {
                Payment::query()
                    ->whereIn('order_id', $orderIds)
                    ->where('status', PaymentStatus::Pending->value)
                    ->update([
                        'status' => PaymentStatus::Expired->value,
                        'updated_at' => $now,
                    ]);
            }
        }

        DB::afterCommit(function () use ($expiredBookings, $lockedSession): void {
            $notifier = app(EtmsNotificationService::class);

            foreach ($expiredBookings as $expiredBooking) {
                if (! $expiredBooking->user) {
                    continue;
                }

                $notifier->notifyUser(
                    user: $expiredBooking->user,
                    eventType: 'booking.expired',
                    title: "Seat Reservation Expired: {$lockedSession->title}",
                    message: "Your 15-minute reservation ({$expiredBooking->booking_reference}) for {$lockedSession->title} expired because payment was not completed in time. You may place a new booking if seats remain open.",
                    actionUrl: route('sessions.show', $lockedSession, false),
                    actionLabel: 'Check Seat Availability',
                    details: [
                        'Booking Reference' => $expiredBooking->booking_reference,
                        'Session Code' => $lockedSession->session_code,
                    ],
                    reference: $expiredBooking->booking_reference,
                );
            }
        });

        return $expiredBookings->count();
    }

    /**
     * Recompute active reserved and confirmed counts directly from `bookings` (D-033).
     *
     * @return array{0: int, 1: int}
     */
    protected function computeActiveCountsLocked(int $sessionId): array
    {
        $now = now();

        $activeReserved = Booking::query()
            ->where('training_session_id', $sessionId)
            ->where('status', BookingStatus::PendingPayment->value)
            ->where('reservation_expires_at', '>', $now)
            ->count();

        $activeConfirmed = Booking::query()
            ->where('training_session_id', $sessionId)
            ->where('status', BookingStatus::Confirmed->value)
            ->count();

        return [$activeReserved, $activeConfirmed];
    }

    /**
     * Synchronize `reserved_seats_count`, `confirmed_seats_count`, and `status` on a locked session.
     */
    protected function syncSessionCountersLocked(TrainingSession $lockedSession): void
    {
        if ($lockedSession->status === SessionStatus::Cancelled) {
            $lockedSession->reserved_seats_count = 0;
            $lockedSession->confirmed_seats_count = 0;
            $lockedSession->save();

            return;
        }

        [$activeReserved, $activeConfirmed] = $this->computeActiveCountsLocked($lockedSession->id);

        $lockedSession->reserved_seats_count = min($lockedSession->capacity, $activeReserved);
        $lockedSession->confirmed_seats_count = min(
            max(0, $lockedSession->capacity - $lockedSession->reserved_seats_count),
            $activeConfirmed
        );

        $occupied = $lockedSession->reserved_seats_count + $lockedSession->confirmed_seats_count;
        $now = now();

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

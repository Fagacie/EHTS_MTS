<?php

namespace App\Models;

use App\Enums\RefundMethod;
use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Refund extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'refund_reference',
        'order_id',
        'payment_id',
        'booking_id',
        'user_id',
        'amount',
        'currency',
        'refund_method',
        'provider_disbursement_id',
        'disbursement_reference',
        'disbursement_status',
        'disbursement_payload',
        'reason',
        'status',
        'request_source',
        'requested_by',
        'source_event',
        'approved_by',
        'processed_by',
        'requested_at',
        'approved_at',
        'processed_at',
        'failure_reason',
        'gateway_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refund_method' => RefundMethod::class,
            'disbursement_payload' => 'array',
            'status' => RefundStatus::class,
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
            'gateway_payload' => 'array',
        ];
    }

    public static function generateRefundReference(): string
    {
        do {
            $candidate = 'RFD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (self::query()->where('refund_reference', $candidate)->exists());

        return $candidate;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function formattedAmount(): string
    {
        return 'RM '.number_format((float) $this->amount, 2);
    }
}

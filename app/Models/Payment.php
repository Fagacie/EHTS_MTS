<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Payment extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'payment_reference',
        'order_id',
        'user_id',
        'gateway',
        'payment_method',
        'amount',
        'refunded_amount',
        'currency',
        'status',
        'gateway_bill_id',
        'gateway_transaction_id',
        'gateway_reference_1',
        'gateway_payment_url',
        'gateway_state',
        'gateway_payload',
        'paid_at',
        'failed_at',
        'cancelled_at',
        'expires_at',
        'manual_reference',
        'evidence_storage_disk',
        'evidence_storage_key',
        'verified_by',
        'verified_at',
        'verification_notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'gateway_payload' => 'array',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public static function generatePaymentReference(): string
    {
        do {
            $candidate = 'PAY-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (self::query()->where('payment_reference', $candidate)->exists());

        return $candidate;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(PaymentWebhookEvent::class);
    }

    public function refundableBalance(): float
    {
        return max(0.0, round(((float) $this->amount) - ((float) $this->refunded_amount), 2));
    }

    public function formattedAmount(): string
    {
        return 'RM '.number_format((float) $this->amount, 2);
    }

    public function formattedRefundedAmount(): string
    {
        return 'RM '.number_format((float) $this->refunded_amount, 2);
    }
}

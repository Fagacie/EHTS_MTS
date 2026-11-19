<?php

namespace App\Models;

use App\Enums\WebhookProcessingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhookEvent extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'gateway',
        'idempotency_key',
        'request_fingerprint',
        'gateway_bill_id',
        'gateway_collection_id',
        'gateway_transaction_id',
        'payment_id',
        'order_id',
        'event_type',
        'signature_valid',
        'reported_amount',
        'reported_paid_amount',
        'reported_paid',
        'reported_state',
        'reported_paid_at',
        'reported_currency',
        'headers',
        'payload',
        'processing_status',
        'rejection_reason',
        'source_ip',
        'received_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'reported_amount' => 'decimal:2',
            'reported_paid_amount' => 'decimal:2',
            'reported_paid' => 'boolean',
            'reported_paid_at' => 'datetime',
            'headers' => 'array',
            'payload' => 'array',
            'processing_status' => WebhookProcessingStatus::class,
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

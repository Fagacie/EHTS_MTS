<?php

namespace App\Models;

use App\Enums\OrderItemType;
use App\Enums\OrderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'order_id',
        'order_type',
        'item_type',
        'training_session_id',
        'module_id',
        'item_code_snapshot',
        'item_title_snapshot',
        'unit_price',
        'quantity',
        'line_total',
        'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'order_type' => OrderType::class,
            'item_type' => OrderItemType::class,
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'line_total' => 'decimal:2',
            'metadata_snapshot' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    public function moduleEntitlement(): HasOne
    {
        return $this->hasOne(ModuleEntitlement::class);
    }
}

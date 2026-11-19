<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Booking extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'booking_reference',
        'user_id',
        'training_session_id',
        'order_id',
        'order_item_id',
        'status',
        'reserved_at',
        'reservation_expires_at',
        'confirmed_at',
        'expired_at',
        'cancelled_at',
        'refunded_at',
        'cancelled_by',
        'cancellation_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'reserved_at' => 'datetime',
            'reservation_expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'expired_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public static function generateBookingReference(): string
    {
        do {
            $candidate = 'BKG-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (self::query()->where('booking_reference', $candidate)->exists());

        return $candidate;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function isHoldActive(): bool
    {
        return $this->status === BookingStatus::PendingPayment
            && $this->reservation_expires_at !== null
            && $this->reservation_expires_at->isFuture();
    }
}

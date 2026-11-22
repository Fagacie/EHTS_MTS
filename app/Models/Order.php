<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'order_number',
        'user_id',
        'order_type',
        'status',
        'currency',
        'subtotal_amount',
        'total_amount',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_type',
        'company_name',
        'company_registration_no',
        'billing_contact_name',
        'billing_email',
        'billing_phone',
        'billing_address_line_1',
        'billing_address_line_2',
        'billing_city',
        'billing_state',
        'billing_postcode',
        'billing_country',
        'expires_at',
        'paid_at',
        'cancelled_at',
        'refunded_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'order_type' => OrderType::class,
            'status' => OrderStatus::class,
            'subtotal_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public static function generateOrderNumber(): string
    {
        do {
            $candidate = 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (self::query()->where('order_number', $candidate)->exists());

        return $candidate;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class)->orderByDesc('id');
    }

    public function moduleEntitlements(): HasMany
    {
        return $this->hasMany(ModuleEntitlement::class);
    }

    public function formattedTotal(): string
    {
        return $this->currency.' '.number_format((float) $this->total_amount, 2);
    }
}

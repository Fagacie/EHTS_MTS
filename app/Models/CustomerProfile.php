<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'phone',
        'job_title',
        'billing_contact_name',
        'billing_email',
        'billing_phone',
        'billing_address_line_1',
        'billing_address_line_2',
        'billing_city',
        'billing_state',
        'billing_postcode',
        'billing_country',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isComplete(): bool
    {
        return filled($this->phone);
    }

    public function formattedBillingAddress(): ?string
    {
        $parts = array_filter([
            $this->billing_address_line_1,
            $this->billing_address_line_2,
            trim(($this->billing_postcode ?? '').' '.($this->billing_city ?? '')),
            $this->billing_state,
            $this->billing_country,
        ]);

        return $parts !== [] ? implode(', ', $parts) : null;
    }
}

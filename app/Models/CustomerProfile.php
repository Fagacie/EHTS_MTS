<?php

namespace App\Models;

use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'customer_type',
        'phone',
        'job_title',
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
    ];

    protected function casts(): array
    {
        return [
            'customer_type' => CustomerType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompany(): bool
    {
        return $this->customer_type === CustomerType::Company;
    }

    public function isComplete(): bool
    {
        if (blank($this->phone)) {
            return false;
        }

        if ($this->isCompany()) {
            return filled($this->company_name)
                && filled($this->billing_address_line_1)
                && filled($this->billing_city)
                && filled($this->billing_state)
                && filled($this->billing_postcode);
        }

        return true;
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

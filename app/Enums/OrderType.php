<?php

namespace App\Enums;

enum OrderType: string
{
    case SessionBooking = 'session_booking';
    case ModulePurchase = 'module_purchase';

    public function label(): string
    {
        return match ($this) {
            self::SessionBooking => 'Physical Session Booking',
            self::ModulePurchase => 'Independent Module Purchase',
        };
    }
}

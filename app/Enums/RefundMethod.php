<?php

namespace App\Enums;

enum RefundMethod: string
{
    case PaymentOrder = 'payment_order';
    case ManualBankTransfer = 'manual_bank_transfer';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PaymentOrder => 'Billplz Payment Order',
            self::ManualBankTransfer => 'Manual Bank Transfer',
            self::Other => 'Other Approved Channel',
        };
    }
}

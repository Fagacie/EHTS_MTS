<?php

namespace App\Enums;

enum CertificateStatus: string
{
    case Pending = 'pending';
    case Issued = 'issued';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Generation',
            self::Issued => 'Issued',
            self::Revoked => 'Revoked',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Issued => 'success',
            self::Revoked => 'danger',
        };
    }
}

<?php

namespace App\Enums;

enum SessionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Full = 'full';
    case RegistrationClosed = 'registration_closed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Registration Open',
            self::Full => 'Session Full',
            self::RegistrationClosed => 'Registration Closed',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft, self::RegistrationClosed => 'gray',
            self::Published, self::Completed => 'success',
            self::Full => 'warning',
            self::InProgress => 'info',
            self::Cancelled => 'danger',
        };
    }
}

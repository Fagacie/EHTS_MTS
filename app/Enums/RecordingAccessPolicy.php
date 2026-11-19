<?php

namespace App\Enums;

enum RecordingAccessPolicy: string
{
    case AttendeesOnly = 'attendees_only';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::AttendeesOnly => 'Verified Present Attendees Only (D-017)',
            self::None => 'No Recording Access',
        };
    }
}

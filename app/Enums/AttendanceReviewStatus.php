<?php

namespace App\Enums;

enum AttendanceReviewStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Verified = 'verified';
    case CorrectionRequested = 'correction_requested';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted for Review',
            self::Verified => 'Verified',
            self::CorrectionRequested => 'Correction Requested',
        };
    }
}

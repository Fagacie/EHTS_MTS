<?php

namespace App\Enums;

enum WebhookProcessingStatus: string
{
    case Received = 'received';
    case UnmatchedPending = 'unmatched_pending';
    case Processed = 'processed';
    case IgnoredDuplicate = 'ignored_duplicate';
    case RejectedInvalidSignature = 'rejected_invalid_signature';
    case RejectedMismatch = 'rejected_mismatch';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Received',
            self::UnmatchedPending => 'Unmatched Pending',
            self::Processed => 'Processed',
            self::IgnoredDuplicate => 'Ignored Duplicate',
            self::RejectedInvalidSignature => 'Rejected (Invalid Signature)',
            self::RejectedMismatch => 'Rejected (Mismatch)',
            self::Failed => 'Failed',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Processed => 'success',
            self::Received, self::UnmatchedPending => 'warning',
            self::IgnoredDuplicate => 'info',
            self::RejectedInvalidSignature, self::RejectedMismatch, self::Failed => 'danger',
        };
    }
}

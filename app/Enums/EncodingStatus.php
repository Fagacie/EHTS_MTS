<?php

namespace App\Enums;

enum EncodingStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Queued for Encoding',
            self::Processing => 'Processing Video',
            self::Ready => 'Video Ready',
            self::Failed => 'Encoding Failed',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending, self::Processing => 'warning',
            self::Ready => 'success',
            self::Failed => 'danger',
        };
    }
}

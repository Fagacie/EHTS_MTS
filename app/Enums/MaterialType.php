<?php

namespace App\Enums;

enum MaterialType: string
{
    case Pdf = 'pdf';
    case Presentation = 'presentation';
    case Document = 'document';
    case Image = 'image';
    case Worksheet = 'worksheet';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'PDF Document',
            self::Presentation => 'Presentation Deck',
            self::Document => 'Reference Document',
            self::Image => 'Diagram / Image',
            self::Worksheet => 'Technical Worksheet',
            self::Other => 'Supporting File',
        };
    }
}

<?php

namespace App\Enums;

enum PdfGenerationStatus: string
{
    case Queued = 'queued';
    case Generating = 'generating';
    case Completed = 'completed';
    case Failed = 'failed';
}

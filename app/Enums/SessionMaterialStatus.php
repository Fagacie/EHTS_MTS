<?php

namespace App\Enums;

enum SessionMaterialStatus: string
{
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';
}

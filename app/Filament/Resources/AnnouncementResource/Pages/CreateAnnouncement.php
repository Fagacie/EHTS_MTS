<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Enums\AnnouncementStatus;
use App\Filament\Resources\AnnouncementResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['status'] = AnnouncementStatus::Draft->value;

        return $data;
    }
}

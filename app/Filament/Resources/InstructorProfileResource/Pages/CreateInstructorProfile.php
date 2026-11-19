<?php

namespace App\Filament\Resources\InstructorProfileResource\Pages;

use App\Filament\Resources\InstructorProfileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstructorProfile extends CreateRecord
{
    protected static string $resource = InstructorProfileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['photo_storage_disk'] = 'public';

        return $data;
    }
}

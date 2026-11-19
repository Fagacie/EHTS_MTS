<?php

namespace App\Filament\Resources\InstructorProfileResource\Pages;

use App\Filament\Resources\InstructorProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInstructorProfile extends EditRecord
{
    protected static string $resource = InstructorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

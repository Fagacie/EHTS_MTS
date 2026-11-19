<?php

namespace App\Filament\Resources\InstructorProfileResource\Pages;

use App\Filament\Resources\InstructorProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstructorProfiles extends ListRecords
{
    protected static string $resource = InstructorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

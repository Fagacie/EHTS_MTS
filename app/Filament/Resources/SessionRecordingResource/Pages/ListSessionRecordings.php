<?php

namespace App\Filament\Resources\SessionRecordingResource\Pages;

use App\Filament\Resources\SessionRecordingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSessionRecordings extends ListRecords
{
    protected static string $resource = SessionRecordingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

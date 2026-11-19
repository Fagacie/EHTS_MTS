<?php

namespace App\Filament\Resources\ModuleVideoResource\Pages;

use App\Filament\Resources\ModuleVideoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListModuleVideos extends ListRecords
{
    protected static string $resource = ModuleVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\ModuleMaterialResource\Pages;

use App\Filament\Resources\ModuleMaterialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListModuleMaterials extends ListRecords
{
    protected static string $resource = ModuleMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

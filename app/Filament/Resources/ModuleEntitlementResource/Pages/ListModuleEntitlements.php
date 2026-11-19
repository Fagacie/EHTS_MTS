<?php

namespace App\Filament\Resources\ModuleEntitlementResource\Pages;

use App\Filament\Resources\ModuleEntitlementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListModuleEntitlements extends ListRecords
{
    protected static string $resource = ModuleEntitlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Grant Manual Module Access'),
        ];
    }
}

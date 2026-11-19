<?php

namespace App\Filament\Resources\ModuleMaterialResource\Pages;

use App\Filament\Resources\ModuleMaterialResource;
use App\Models\AuditLog;
use App\Models\ModuleMaterial;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateModuleMaterial extends CreateRecord
{
    protected static string $resource = ModuleMaterialResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ModuleMaterial::enrichStorageMetadata($data, Auth::id());
    }

    protected function afterCreate(): void
    {
        /** @var ModuleMaterial $record */
        $record = $this->record;

        AuditLog::record(
            event: 'module_material.created',
            auditableType: 'module_materials',
            auditableId: $record->id,
            newValues: [
                'module_id' => $record->module_id,
                'title' => $record->title,
                'material_type' => $record->material_type->value,
                'status' => $record->status->value,
            ],
        );
    }
}

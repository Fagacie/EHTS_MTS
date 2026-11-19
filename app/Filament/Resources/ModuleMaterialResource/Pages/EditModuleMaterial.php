<?php

namespace App\Filament\Resources\ModuleMaterialResource\Pages;

use App\Filament\Resources\ModuleMaterialResource;
use App\Models\AuditLog;
use App\Models\ModuleMaterial;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditModuleMaterial extends EditRecord
{
    protected static string $resource = ModuleMaterialResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ModuleMaterial::enrichStorageMetadata($data, Auth::id());
    }

    protected function afterSave(): void
    {
        /** @var ModuleMaterial $record */
        $record = $this->record;

        AuditLog::record(
            event: 'module_material.updated',
            auditableType: 'module_materials',
            auditableId: $record->id,
            newValues: [
                'module_id' => $record->module_id,
                'title' => $record->title,
                'status' => $record->status->value,
            ],
        );
    }
}

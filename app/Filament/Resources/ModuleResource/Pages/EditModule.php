<?php

namespace App\Filament\Resources\ModuleResource\Pages;

use App\Enums\ModuleStatus;
use App\Filament\Resources\ModuleResource;
use App\Models\AuditLog;
use App\Models\Module;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditModule extends EditRecord
{
    protected static string $resource = ModuleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        if (! empty($data['cover_image_path'])) {
            $data['cover_image_disk'] = 'public';
        }

        if (! ($data['is_available_independently'] ?? true)) {
            $data['independent_price'] = null;
        }

        if (($data['status'] ?? null) === ModuleStatus::Published->value && empty($this->record->published_at)) {
            $data['published_at'] = now();
        }

        if (($data['status'] ?? null) === ModuleStatus::Archived->value && empty($this->record->archived_at)) {
            $data['archived_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Module $record */
        $record = $this->record;

        AuditLog::record(
            event: 'module.updated',
            auditableType: 'modules',
            auditableId: $record->id,
            newValues: [
                'code' => $record->code,
                'title' => $record->title,
                'status' => $record->status->value,
            ],
        );
    }
}

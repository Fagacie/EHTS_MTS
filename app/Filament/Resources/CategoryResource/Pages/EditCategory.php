<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Models\AuditLog;
use App\Models\Category;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function afterSave(): void
    {
        /** @var Category $record */
        $record = $this->record;

        AuditLog::record(
            event: 'category.updated',
            auditableType: 'categories',
            auditableId: $record->id,
            newValues: [
                'name' => $record->name,
                'slug' => $record->slug,
                'is_active' => $record->is_active,
            ],
        );
    }
}

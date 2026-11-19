<?php

namespace App\Filament\Resources\ModuleVideoResource\Pages;

use App\Filament\Resources\ModuleVideoResource;
use App\Jobs\ProcessVideoToHlsJob;
use App\Models\ModuleVideo;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditModuleVideo extends EditRecord
{
    protected static string $resource = ModuleVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('source_storage_key') && filled($this->record->source_storage_key)) {
            ProcessVideoToHlsJob::dispatch(ModuleVideo::class, $this->record->id);
        }
    }
}

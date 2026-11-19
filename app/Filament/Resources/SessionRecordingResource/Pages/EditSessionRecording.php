<?php

namespace App\Filament\Resources\SessionRecordingResource\Pages;

use App\Filament\Resources\SessionRecordingResource;
use App\Jobs\ProcessVideoToHlsJob;
use App\Models\SessionRecording;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSessionRecording extends EditRecord
{
    protected static string $resource = SessionRecordingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('source_storage_key') && filled($this->record->source_storage_key)) {
            ProcessVideoToHlsJob::dispatch(SessionRecording::class, $this->record->id);
        }
    }
}

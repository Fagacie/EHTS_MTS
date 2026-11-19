<?php

namespace App\Filament\Resources\SessionRecordingResource\Pages;

use App\Enums\EncodingStatus;
use App\Enums\RecordingStatus;
use App\Filament\Resources\SessionRecordingResource;
use App\Jobs\ProcessVideoToHlsJob;
use App\Models\SessionRecording;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateSessionRecording extends CreateRecord
{
    protected static string $resource = SessionRecordingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['provider'] = 'local_hls';
        $data['provider_library_id'] = 'local';
        $data['storage_disk'] = 'recordings';
        $data['submitted_by'] = Auth::id();
        $data['submitted_at'] = now();

        if (blank($data['provider_video_id'] ?? null)) {
            $data['provider_video_id'] = 'rec-ses-'.strtolower(Str::random(10));
        }

        if (($data['status'] ?? null) === RecordingStatus::Published->value) {
            $data['reviewed_by'] = Auth::id();
            $data['reviewed_at'] = now();
            $data['published_at'] = now();
        }

        if (filled($data['source_storage_key'] ?? null) && blank($data['hls_manifest_key'] ?? null)) {
            $data['encoding_status'] = EncodingStatus::Pending->value;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if (filled($this->record->source_storage_key) && blank($this->record->hls_manifest_key)) {
            ProcessVideoToHlsJob::dispatch(SessionRecording::class, $this->record->id);
        }
    }
}

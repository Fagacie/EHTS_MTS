<?php

namespace App\Filament\Resources\ModuleVideoResource\Pages;

use App\Enums\EncodingStatus;
use App\Enums\MaterialStatus;
use App\Filament\Resources\ModuleVideoResource;
use App\Jobs\ProcessVideoToHlsJob;
use App\Models\ModuleVideo;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateModuleVideo extends CreateRecord
{
    protected static string $resource = ModuleVideoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['provider'] = 'local_hls';
        $data['provider_library_id'] = 'local';
        $data['storage_disk'] = 'videos';
        $data['uploaded_by'] = Auth::id();

        if (blank($data['provider_video_id'] ?? null)) {
            $data['provider_video_id'] = 'vid-mod-'.strtolower(Str::random(10));
        }

        if (($data['status'] ?? null) === MaterialStatus::Published->value && empty($data['published_at'])) {
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
            ProcessVideoToHlsJob::dispatch(ModuleVideo::class, $this->record->id);
        }
    }
}

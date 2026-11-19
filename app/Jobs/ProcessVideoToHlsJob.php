<?php

namespace App\Jobs;

use App\Enums\EncodingStatus;
use App\Models\AuditLog;
use App\Models\ModuleVideo;
use App\Models\SessionRecording;
use App\Services\Media\SelfHostedVideoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessVideoToHlsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800;

    public int $tries = 2;

    /**
     * @param  class-string<ModuleVideo|SessionRecording>  $modelClass
     */
    public function __construct(
        public string $modelClass,
        public int $recordId,
    ) {
        $this->onQueue('videos');
    }

    public function handle(SelfHostedVideoService $videoService): void
    {
        if (! in_array($this->modelClass, [ModuleVideo::class, SessionRecording::class], true)) {
            return;
        }

        /** @var ModuleVideo|SessionRecording|null $record */
        $record = ($this->modelClass)::query()->find($this->recordId);
        if (! $record || blank($record->source_storage_key)) {
            return;
        }

        $disk = $record->storage_disk ?: ($record instanceof ModuleVideo ? 'videos' : 'recordings');
        $outputDir = $record instanceof ModuleVideo
            ? "hls/modules/{$record->module_id}/video-{$record->id}"
            : "hls/sessions/{$record->training_session_id}/recording-{$record->id}";

        $record->update([
            'encoding_status' => EncodingStatus::Processing,
            'processing_error' => null,
        ]);

        try {
            $result = $videoService->transcodeToHls($disk, (string) $record->source_storage_key, $outputDir);

            $record->update([
                'provider' => 'local_hls',
                'provider_library_id' => 'local',
                'storage_disk' => $disk,
                'hls_manifest_key' => $result['manifest_key'],
                'duration_seconds' => $result['duration_seconds'] ?? $record->duration_seconds,
                'file_size_bytes' => $result['file_size_bytes'],
                'mime_type' => $result['mime_type'],
                'encoding_status' => EncodingStatus::Ready,
                'processing_error' => null,
            ]);

            AuditLog::record(
                event: 'video.hls_transcoded',
                auditableType: $this->modelClass,
                auditableId: $record->id,
                oldValues: null,
                newValues: [
                    'disk' => $disk,
                    'hls_manifest_key' => $result['manifest_key'],
                    'duration_seconds' => $result['duration_seconds'],
                ],
                reason: 'Self-hosted FFmpeg HLS transcoding completed.',
            );
        } catch (Throwable $e) {
            $record->update([
                'encoding_status' => EncodingStatus::Failed,
                'processing_error' => mb_substr($e->getMessage(), 0, 2000),
            ]);

            throw $e;
        }
    }
}

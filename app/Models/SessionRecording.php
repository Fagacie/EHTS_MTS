<?php

namespace App\Models;

use App\Enums\EncodingStatus;
use App\Enums\RecordingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;

class SessionRecording extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'training_session_id',
        'title',
        'description',
        'provider',
        'provider_library_id',
        'provider_video_id',
        'storage_disk',
        'source_storage_key',
        'hls_manifest_key',
        'original_filename',
        'file_size_bytes',
        'mime_type',
        'duration_seconds',
        'thumbnail_url',
        'encoding_status',
        'processing_error',
        'status',
        'sort_order',
        'submitted_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'duration_seconds' => 'integer',
            'encoding_status' => EncodingStatus::class,
            'status' => RecordingStatus::class,
            'sort_order' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPlayable(): bool
    {
        return $this->encoding_status === EncodingStatus::Ready
            && (! empty($this->hls_manifest_key) || ! empty($this->source_storage_key));
    }

    public function formattedDuration(): string
    {
        if (! $this->duration_seconds || $this->duration_seconds <= 0) {
            return '—';
        }

        $minutes = intdiv($this->duration_seconds, 60);
        $seconds = $this->duration_seconds % 60;

        if ($minutes >= 60) {
            $hours = intdiv($minutes, 60);
            $remMinutes = $minutes % 60;

            return sprintf('%dh %02dm', $hours, $remMinutes);
        }

        return sprintf('%dm %02ds', $minutes, $seconds);
    }

    public function temporaryWatchUrl(int $minutes = 30): string
    {
        return URL::temporarySignedRoute(
            'portal.recordings.watch',
            now()->addMinutes($minutes),
            ['recording' => $this->id],
        );
    }
}

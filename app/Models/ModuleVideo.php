<?php

namespace App\Models;

use App\Enums\EncodingStatus;
use App\Enums\MaterialStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;

class ModuleVideo extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'module_id',
        'title',
        'description',
        'provider',
        'provider_library_id',
        'provider_video_id',
        'provider_collection_id',
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
        'sort_order',
        'status',
        'published_at',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'duration_seconds' => 'integer',
            'encoding_status' => EncodingStatus::class,
            'sort_order' => 'integer',
            'status' => MaterialStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', MaterialStatus::Published->value)
            ->orderBy('sort_order')
            ->orderBy('id');
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
            'portal.modules.videos.watch',
            now()->addMinutes($minutes),
            [
                'module' => $this->module?->slug ?? $this->module_id,
                'video' => $this->id,
            ],
        );
    }
}

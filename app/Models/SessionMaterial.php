<?php

namespace App\Models;

use App\Enums\MaterialType;
use App\Enums\SessionMaterialStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;

class SessionMaterial extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'training_session_id',
        'title',
        'description',
        'material_type',
        'storage_disk',
        'storage_key',
        'original_filename',
        'mime_type',
        'file_size_bytes',
        'checksum_sha256',
        'is_downloadable',
        'sort_order',
        'is_main_document',
        'status',
        'uploaded_by',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'material_type' => MaterialType::class,
            'file_size_bytes' => 'integer',
            'is_downloadable' => 'boolean',
            'sort_order' => 'integer',
            'is_main_document' => 'boolean',
            'status' => SessionMaterialStatus::class,
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function formattedFileSize(): string
    {
        $bytes = (int) ($this->file_size_bytes ?? 0);
        if ($bytes <= 0) {
            return '—';
        }
        if ($bytes < 1024) {
            return "{$bytes} B";
        }
        $kb = $bytes / 1024;
        if ($kb < 1024) {
            return number_format($kb, 1).' KB';
        }

        return number_format($kb / 1024, 2).' MB';
    }

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(SessionDocumentEntitlement::class, 'session_material_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function temporaryDownloadUrl(int $minutes = 15): string
    {
        return URL::temporarySignedRoute('session-materials.download', now()->addMinutes($minutes), ['material' => $this->id]);
    }

    public static function enrichStorageMetadata(array $data, int $uploadedBy): array
    {
        $data['uploaded_by'] = $uploadedBy;
        $data['storage_disk'] = 'session-materials';

        if (isset($data['storage_key']) && is_string($data['storage_key'])) {
            $disk = \Illuminate\Support\Facades\Storage::disk('session-materials');
            $data['original_filename'] = $data['original_filename'] ?? basename((string) $data['storage_key']);
            $data['mime_type'] = $disk->mimeType($data['storage_key']);
            $data['file_size_bytes'] = $disk->size($data['storage_key']);
            $data['checksum_sha256'] = $disk->exists($data['storage_key'])
                ? hash('sha256', $disk->get($data['storage_key']))
                : null;
        }

        return $data;
    }
}

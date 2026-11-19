<?php

namespace App\Models;

use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ModuleMaterial extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'module_id',
        'title',
        'description',
        'material_type',
        'storage_disk',
        'storage_bucket',
        'storage_key',
        'original_filename',
        'mime_type',
        'file_size_bytes',
        'checksum_sha256',
        'is_downloadable',
        'sort_order',
        'status',
        'published_at',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'material_type' => MaterialType::class,
            'file_size_bytes' => 'integer',
            'is_downloadable' => 'boolean',
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

    public function isPublished(): bool
    {
        return $this->status === MaterialStatus::Published;
    }

    public function temporaryDownloadUrl(int $minutes = 15): string
    {
        return URL::temporarySignedRoute(
            'materials.download',
            now()->addMinutes($minutes),
            ['material' => $this->id],
        );
    }

    public function formattedFileSize(): string
    {
        $bytes = max(0, (int) $this->file_size_bytes);

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function enrichStorageMetadata(array $data, ?int $actorId = null): array
    {
        $disk = (string) ($data['storage_disk'] ?? 'materials');
        $data['storage_disk'] = $disk;
        $data['storage_bucket'] ??= 'etms-server-storage';

        if ($actorId !== null) {
            $data['uploaded_by'] ??= $actorId;
        }

        $status = $data['status'] instanceof MaterialStatus ? $data['status']->value : ($data['status'] ?? MaterialStatus::Draft->value);
        if ($status === MaterialStatus::Published->value && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $storageKey = (string) ($data['storage_key'] ?? '');
        if ($storageKey !== '') {
            $data['original_filename'] = ! empty($data['original_filename'])
                ? (string) $data['original_filename']
                : basename($storageKey);

            try {
                if (Storage::disk($disk)->exists($storageKey)) {
                    $size = (int) Storage::disk($disk)->size($storageKey);
                    $data['file_size_bytes'] = max(1, $size);
                    $data['mime_type'] = Storage::disk($disk)->mimeType($storageKey) ?: ($data['mime_type'] ?? 'application/pdf');
                    $contents = Storage::disk($disk)->get($storageKey);
                    if ($contents !== null) {
                        $data['checksum_sha256'] = hash('sha256', $contents);
                    }
                }
            } catch (\Throwable) {
                // Fallback to supplied metadata
            }
        }

        $data['original_filename'] = ! empty($data['original_filename']) ? (string) $data['original_filename'] : 'document.pdf';
        $data['mime_type'] = ! empty($data['mime_type']) ? (string) $data['mime_type'] : 'application/pdf';
        $data['file_size_bytes'] = max(1, (int) ($data['file_size_bytes'] ?? 1024));

        return $data;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class InstructorProfile extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'display_name',
        'professional_title',
        'qualifications',
        'short_bio',
        'biography',
        'specialisations',
        'photo_storage_disk',
        'photo_storage_key',
        'is_public',
        'published_at',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'specialisations' => 'array',
            'is_public' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true)
            ->whereHas('user', fn (Builder $q) => $q->where('status', 'active'));
    }

    public function resolvedDisplayName(): string
    {
        return $this->display_name ?: ($this->user?->name ?? 'ETMS Instructor');
    }

    public function photoUrl(): ?string
    {
        if (blank($this->photo_storage_key)) {
            return null;
        }

        $disk = $this->photo_storage_disk ?: 'public';

        return Storage::disk($disk)->url($this->photo_storage_key);
    }
}

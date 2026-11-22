<?php

namespace App\Models;

use App\Enums\MaterialStatus;
use App\Enums\ModuleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Module extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'category_id',
        'code',
        'title',
        'slug',
        'summary',
        'description',
        'learning_objectives',
        'cover_image_disk',
        'cover_image_path',
        'is_available_independently',
        'independent_price',
        'currency',
        'status',
        'published_at',
        'archived_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'learning_objectives' => 'array',
            'is_available_independently' => 'boolean',
            'independent_price' => 'decimal:2',
            'status' => ModuleStatus::class,
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ModuleMaterial::class)->orderBy('sort_order')->orderBy('id');
    }

    public function publishedMaterials(): HasMany
    {
        return $this->hasMany(ModuleMaterial::class)
            ->where('status', MaterialStatus::Published->value)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(ModuleVideo::class)->orderBy('sort_order')->orderBy('id');
    }

    public function publishedVideos(): HasMany
    {
        return $this->hasMany(ModuleVideo::class)
            ->where('status', MaterialStatus::Published->value)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class)->orderBy('starts_at');
    }

    public function upcomingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class)
            ->whereIn('status', ['published', 'full', 'registration_closed'])
            ->where('starts_at', '>', now())
            ->orderBy('starts_at');
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(ModuleEntitlement::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ModuleStatus::Published->value)
            ->where(function (Builder $q): void {
                $q->whereNull('category_id')
                    ->orWhereHas('category', fn (Builder $cq) => $cq->where('is_active', true));
            });
    }

    public function isPublished(): bool
    {
        return $this->status === ModuleStatus::Published;
    }

    public function formattedIndependentPrice(): ?string
    {
        if (! $this->is_available_independently || $this->independent_price === null) {
            return null;
        }

        return ($this->currency ?: config('etms.currency')).' '.number_format((float) $this->independent_price, 2);
    }

    public function coverImageUrl(): ?string
    {
        if (blank($this->cover_image_path)) {
            return null;
        }

        $disk = $this->cover_image_disk ?: 'public';

        return Storage::disk($disk)->url($this->cover_image_path);
    }
}

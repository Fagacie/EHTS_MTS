<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'setting_key',
        'setting_group',
        'value_type',
        'setting_value',
        'description',
        'is_public',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::saved(static function (self $setting): void {
            Cache::forget(self::cacheKey($setting->setting_key));
        });

        static::deleted(static function (self $setting): void {
            Cache::forget(self::cacheKey($setting->setting_key));
        });
    }

    protected function casts(): array
    {
        return [
            'setting_value' => 'json',
            'is_public' => 'boolean',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function cacheKey(string $key): string
    {
        return "system_setting:{$key}";
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        try {
            /** @var array{found: bool, value: mixed} $entry */
            $entry = Cache::remember(
                self::cacheKey($key),
                now()->addSeconds(300),
                static function () use ($key): array {
                    $setting = self::query()->where('setting_key', $key)->first();

                    if (! $setting || $setting->setting_value === null) {
                        return ['found' => false, 'value' => null];
                    }

                    $resolved = match ($setting->value_type) {
                        'integer' => (int) $setting->setting_value,
                        'boolean' => (bool) $setting->setting_value,
                        default => $setting->setting_value,
                    };

                    return ['found' => true, 'value' => $resolved];
                },
            );
        } catch (QueryException) {
            return $default;
        }

        return $entry['found'] ? $entry['value'] : $default;
    }

    public static function setValue(
        string $key,
        mixed $value,
        string $group = 'general',
        string $type = 'string',
        ?string $description = null,
        bool $isPublic = false,
        ?int $updatedBy = null,
    ): self {
        Cache::forget(self::cacheKey($key));

        $record = self::query()->updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_group' => $group,
                'value_type' => $type,
                'setting_value' => $value,
                'description' => $description,
                'is_public' => $isPublic,
                'updated_by' => $updatedBy,
            ],
        );

        Cache::forget(self::cacheKey($key));

        return $record;
    }
}

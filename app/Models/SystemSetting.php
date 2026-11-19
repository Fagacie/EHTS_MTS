<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

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

    public static function getValue(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('system_settings')) {
            return $default;
        }

        $setting = self::query()->where('setting_key', $key)->first();
        if (! $setting || $setting->setting_value === null) {
            return $default;
        }

        return match ($setting->value_type) {
            'integer' => (int) $setting->setting_value,
            'boolean' => (bool) $setting->setting_value,
            default => $setting->setting_value,
        };
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
        return self::query()->updateOrCreate(
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
    }
}

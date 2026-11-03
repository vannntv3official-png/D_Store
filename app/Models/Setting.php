<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /**
     * In-memory request cache to prevent redundant DB calls in the same lifecycle.
     * @var array<string, mixed>|null
     */
    protected static ?array $runtimeCache = null;

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }

    public static function clearCache(): void
    {
        static::$runtimeCache = null;
        try {
            Cache::forget('dstore_all_settings');
            Cache::forget('dstore_all_settings_data');
        } catch (\Throwable) {
            // Ignore during early migrations / bootstrap
        }
    }

    /**
     * Retrieve all settings mapped by key as primitive arrays (no Eloquent objects in cache), cached for 1 hour.
     * @return array<string, array{value: mixed, type: string}>
     */
    protected static function allSettings(): array
    {
        if (static::$runtimeCache !== null) {
            return static::$runtimeCache;
        }

        try {
            $cached = Cache::remember('dstore_all_settings_data', 3600, function () {
                return static::query()
                    ->get(['key', 'value', 'type'])
                    ->mapWithKeys(fn ($item) => [
                        $item->key => [
                            'value' => $item->value,
                            'type' => $item->type ?? 'string',
                        ],
                    ])
                    ->all();
            });

            static::$runtimeCache = is_array($cached) ? $cached : [];
        } catch (\Throwable) {
            try {
                static::$runtimeCache = static::query()
                    ->get(['key', 'value', 'type'])
                    ->mapWithKeys(fn ($item) => [
                        $item->key => [
                            'value' => $item->value,
                            'type' => $item->type ?? 'string',
                        ],
                    ])
                    ->all();
            } catch (\Throwable) {
                static::$runtimeCache = [];
            }
        }

        return static::$runtimeCache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::allSettings();
        $setting = $all[$key] ?? null;

        if (!$setting) {
            return $default;
        }

        $val = $setting['value'] ?? null;
        $type = $setting['type'] ?? 'string';

        return match ($type) {
            'json' => is_array($val) ? $val : (json_decode((string) $val, true) ?: $default),
            'boolean' => filter_var($val, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($val) ? $val + 0 : $default,
            default => $val,
        };
    }

    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'string', bool $isPublic = false): void
    {
        $stringValue = match ($type) {
            'json' => is_string($value) ? $value : json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'group' => $group,
                'type' => $type,
                'is_public' => $isPublic,
            ]
        );

        static::clearCache();
    }
}

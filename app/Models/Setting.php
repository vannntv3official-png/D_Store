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
        } catch (\Throwable) {
            // Ignore during early migrations / bootstrap
        }
    }

    /**
     * Retrieve all settings mapped by key, cached for 1 hour.
     */
    protected static function allSettings(): array
    {
        if (static::$runtimeCache !== null) {
            return static::$runtimeCache;
        }

        try {
            $settings = Cache::remember('dstore_all_settings', 3600, function () {
                return static::all()->keyBy('key');
            });
        } catch (\Throwable) {
            $settings = static::all()->keyBy('key');
        }

        static::$runtimeCache = $settings ? $settings->all() : [];
        return static::$runtimeCache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::allSettings();
        $setting = $all[$key] ?? null;

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'json' => is_array($setting->value) ? $setting->value : (json_decode($setting->value, true) ?: $default),
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($setting->value) ? $setting->value + 0 : $default,
            default => $setting->value,
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

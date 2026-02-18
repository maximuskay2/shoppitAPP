<?php

namespace App\Modules\Commerce\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use UUID;

    protected $guarded = [];

    protected $hidden = ['id'];

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::remember("system_setting:{$key}", 300, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            return match ($setting->type) {
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $setting->value,
                'json' => json_decode($setting->value, true),
                default => $setting->value,
            };
        });
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, mixed $value, string $type = 'string', ?string $description = null): self
    {
        $stringValue = match ($type) {
            'boolean' => $value ? 'true' : 'false',
            'json' => json_encode($value),
            default => (string) $value,
        };

        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $stringValue, 'type' => $type, 'description' => $description]
        );

        Cache::forget("system_setting:{$key}");

        return $setting;
    }

    /**
     * Check if maintenance mode is enabled
     */
    public static function isMaintenanceMode(): bool
    {
        return self::getValue('maintenance_mode', false);
    }

    /**
     * Clear cache when setting is updated
     */
    protected static function booted(): void
    {
        static::saved(function ($setting) {
            Cache::forget("system_setting:{$setting->key}");
        });

        static::deleted(function ($setting) {
            Cache::forget("system_setting:{$setting->key}");
        });
    }
}

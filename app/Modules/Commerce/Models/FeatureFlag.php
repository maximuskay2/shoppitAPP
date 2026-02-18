<?php

namespace App\Modules\Commerce\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FeatureFlag extends Model
{
    use UUID;

    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
        'conditions' => 'array',
    ];

    protected $hidden = ['id'];

    /**
     * Check if a feature is enabled
     */
    public static function isEnabled(string $key): bool
    {
        return Cache::remember("feature_flag:{$key}", 300, function () use ($key) {
            $flag = self::where('key', $key)->first();
            return $flag?->is_enabled ?? false;
        });
    }

    /**
     * Clear cache when flag is updated
     */
    protected static function booted(): void
    {
        static::saved(function ($flag) {
            Cache::forget("feature_flag:{$flag->key}");
        });

        static::deleted(function ($flag) {
            Cache::forget("feature_flag:{$flag->key}");
        });
    }
}

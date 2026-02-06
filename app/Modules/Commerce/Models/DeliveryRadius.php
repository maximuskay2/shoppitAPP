<?php

namespace App\Modules\Commerce\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryRadius extends Model
{
    use HasFactory, UUID;

    protected $table = 'delivery_radii';

    protected $guarded = [];

    protected $casts = [
        'radius_km' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the active delivery radius (default global radius)
     */
    public static function getActiveRadius(): self
    {
        return self::where('name', 'default')
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * Get radius in kilometers
     */
    public function getRadiusInKm(): float
    {
        return (float) $this->radius_km;
    }
}

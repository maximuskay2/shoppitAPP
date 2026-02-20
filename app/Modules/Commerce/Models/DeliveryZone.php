<?php

namespace App\Modules\Commerce\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    use UUID;

    protected $table = 'delivery_zones';

    protected $guarded = [];

    protected $casts = [
        'base_fee' => 'decimal:2',
        'per_km_fee' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'center_latitude' => 'decimal:7',
        'center_longitude' => 'decimal:7',
        'radius_km' => 'decimal:2',
        'areas' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if a point (lat, lng) falls within this zone's geographic boundary.
     * Uses center + radius (circular zone). Returns true if zone has no geo (legacy).
     */
    public function containsPoint(float $latitude, float $longitude): bool
    {
        if ($this->center_latitude === null || $this->center_longitude === null || $this->radius_km === null) {
            return true; // Legacy zones without geo: allow all
        }

        $distance = \App\Helpers\GeoHelper::calculateDistance(
            (float) $this->center_latitude,
            (float) $this->center_longitude,
            $latitude,
            $longitude
        );

        return $distance <= (float) $this->radius_km;
    }

    /**
     * Check if zone has geographic boundary defined
     */
    public function hasGeoBoundary(): bool
    {
        return $this->center_latitude !== null
            && $this->center_longitude !== null
            && $this->radius_km !== null
            && (float) $this->radius_km > 0;
    }
}

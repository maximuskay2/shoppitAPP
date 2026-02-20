<?php

namespace App\Helpers;

use App\Modules\Commerce\Models\DeliveryRadius;
use App\Modules\Commerce\Models\DeliveryZone;

/**
 * Geographic distance calculations and driver matching utilities
 */
class GeoHelper
{
    /**
     * Earth's radius in kilometers
     */
    const EARTH_RADIUS_KM = 6371;

    /**
     * Calculate distance between two coordinates using Haversine formula
     *
     * @param float $lat1 Starting latitude
     * @param float $lon1 Starting longitude
     * @param float $lat2 Ending latitude
     * @param float $lon2 Ending longitude
     * @return float Distance in kilometers
     */
    public static function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }

    /**
     * Check if driver is within delivery radius of vendor/delivery location
     *
     * @param float $driverLat Driver's current latitude
     * @param float $driverLon Driver's current longitude
     * @param float $targetLat Vendor/Delivery location latitude
     * @param float $targetLon Vendor/Delivery location longitude
     * @param float|null $radiusKm Delivery radius in km (uses default if null)
     * @return bool True if within radius
     */
    public static function isWithinDeliveryRadius(
        float $driverLat,
        float $driverLon,
        float $targetLat,
        float $targetLon,
        ?float $radiusKm = null
    ): bool {
        if ($radiusKm === null) {
            $radiusKm = self::getActiveDeliveryRadius();
        }

        $distance = self::calculateDistance($driverLat, $driverLon, $targetLat, $targetLon);
        return $distance <= $radiusKm;
    }

    /**
     * Get active delivery radius in kilometers
     *
     * @return float Radius in kilometers
     */
    public static function getActiveDeliveryRadius(): float
    {
        try {
            $radius = DeliveryRadius::getActiveRadius();
            return $radius->getRadiusInKm();
        } catch (\Exception $e) {
            // Fallback to 300km if not configured
            return 300.0;
        }
    }

    /**
     * Format distance for display
     *
     * @param float $distanceKm Distance in kilometers
     * @return string Formatted distance string
     */
    public static function formatDistance(float $distanceKm): string
    {
        if ($distanceKm < 1) {
            return round($distanceKm * 1000) . ' meters';
        }
        return number_format($distanceKm, 2) . ' km';
    }

    /**
     * Calculate bounding box for coordinate search (optimization for DB queries)
     * Returns [lat_min, lat_max, lon_min, lon_max] for approximate rectangular search
     *
     * @param float $centerLat Center latitude
     * @param float $centerLon Center longitude
     * @param float $radiusKm Radius in kilometers
     * @return array Bounding box coordinates
     */
    public static function getBoundingBox(
        float $centerLat,
        float $centerLon,
        float $radiusKm
    ): array {
        // 1 degree of latitude ≈ 111 km
        $latOffset = $radiusKm / 111;
        // Longitude offset depends on latitude
        $lonOffset = $radiusKm / (111 * cos(deg2rad($centerLat)));

        return [
            'lat_min' => $centerLat - $latOffset,
            'lat_max' => $centerLat + $latOffset,
            'lon_min' => $centerLon - $lonOffset,
            'lon_max' => $centerLon + $lonOffset,
        ];
    }

    /**
     * Find the first active delivery zone that contains the given point.
     * Used for user/vendor/driver registration validation.
     *
     * @return DeliveryZone|null
     */
    public static function zoneForPoint(float $latitude, float $longitude): ?DeliveryZone
    {
        $zones = DeliveryZone::active()->get();

        foreach ($zones as $zone) {
            if ($zone->containsPoint($latitude, $longitude)) {
                return $zone;
            }
        }

        return null;
    }

    /**
     * Check if any active delivery zone contains the point.
     */
    public static function isPointInDeliveryZone(float $latitude, float $longitude): bool
    {
        return self::zoneForPoint($latitude, $longitude) !== null;
    }
}

<?php

namespace Tests\Unit\Helpers;

use App\Helpers\GeoHelper;
use PHPUnit\Framework\TestCase;

class GeoHelperTest extends TestCase
{
    /**
     * Test Haversine distance calculation with known coordinates
     * Lagos to Abuja: approximately 492 km
     */
    public function test_calculate_distance_lagos_to_abuja()
    {
        $lagosLat = 6.5244;
        $lagosLon = 3.3792;
        $abujLat = 9.0765;
        $abujLon = 7.3986;

        $distance = GeoHelper::calculateDistance($lagosLat, $lagosLon, $abujLat, $abujLon);

        // Allow 1% margin of error
        $this->assertGreaterThan(485, $distance);
        $this->assertLessThan(500, $distance);
    }

    /**
     * Test distance calculation between same coordinates (should return 0)
     */
    public function test_calculate_distance_same_coordinates()
    {
        $lat = 6.5244;
        $lon = 3.3792;

        $distance = GeoHelper::calculateDistance($lat, $lon, $lat, $lon);

        $this->assertEquals(0, $distance, '', 0.001);
    }

    /**
     * Test distance less than 1 km
     */
    public function test_calculate_distance_less_than_one_km()
    {
        $lat1 = 6.5244;
        $lon1 = 3.3792;
        // Approximately 50m away
        $lat2 = 6.5248;
        $lon2 = 3.3796;

        $distance = GeoHelper::calculateDistance($lat1, $lon1, $lat2, $lon2);

        $this->assertLessThan(0.1, $distance);
    }

    /**
     * Test is_within_delivery_radius function
     */
    public function test_is_within_delivery_radius()
    {
        $driverLat = 6.5244;
        $driverLon = 3.3792;
        $vendorLat = 6.5270; // ~2.9km away
        $vendorLon = 3.3850;

        // Within 5km radius
        $this->assertTrue(GeoHelper::isWithinDeliveryRadius(
            $driverLat, $driverLon, $vendorLat, $vendorLon, 5.0
        ));

        // Outside 2km radius
        $this->assertFalse(GeoHelper::isWithinDeliveryRadius(
            $driverLat, $driverLon, $vendorLat, $vendorLon, 2.0
        ));
    }

    /**
     * Test format_distance function
     */
    public function test_format_distance()
    {
        $this->assertEquals('500 meters', GeoHelper::formatDistance(0.5));
        $this->assertEquals('1.50 km', GeoHelper::formatDistance(1.5));
        $this->assertEquals('15.75 km', GeoHelper::formatDistance(15.75));
    }

    /**
     * Test bounding box calculation
     */
    public function test_get_bounding_box()
    {
        $centerLat = 6.5244;
        $centerLon = 3.3792;
        $radiusKm = 15;

        $bbox = GeoHelper::getBoundingBox($centerLat, $centerLon, $radiusKm);

        // Check structure
        $this->assertArrayHasKey('lat_min', $bbox);
        $this->assertArrayHasKey('lat_max', $bbox);
        $this->assertArrayHasKey('lon_min', $bbox);
        $this->assertArrayHasKey('lon_max', $bbox);

        // Check bounds
        $this->assertLessThan($centerLat, $bbox['lat_min']);
        $this->assertGreaterThan($centerLat, $bbox['lat_max']);
        $this->assertLessThan($centerLon, $bbox['lon_min']);
        $this->assertGreaterThan($centerLon, $bbox['lon_max']);
    }
}

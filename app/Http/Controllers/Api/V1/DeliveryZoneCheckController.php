<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\GeoHelper;
use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public endpoint for apps to check if a location is within a delivery zone.
 * Used by user/vendor/driver registration flows.
 */
class DeliveryZoneCheckController extends Controller
{
    /**
     * GET /api/v1/delivery-zones/check?latitude=6.5244&longitude=3.3792
     * Returns whether the point is in an active zone and which zone (if any).
     */
    public function check(Request $request): JsonResponse
    {
        $lat = $request->input('latitude');
        $lng = $request->input('longitude');

        if ($lat === null || $lng === null) {
            return ShopittPlus::response(false, 'latitude and longitude are required', 400);
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return ShopittPlus::response(false, 'Invalid coordinates', 400);
        }

        $zone = GeoHelper::zoneForPoint($lat, $lng);

        if ($zone === null) {
            return ShopittPlus::response(true, 'Location is not within any delivery zone', 200, [
                'in_zone' => false,
                'zone' => null,
            ]);
        }

        return ShopittPlus::response(true, 'Location is within a delivery zone', 200, [
            'in_zone' => true,
            'zone' => [
                'id' => $zone->id,
                'name' => $zone->name,
                'base_fee' => (float) $zone->base_fee,
                'per_km_fee' => (float) $zone->per_km_fee,
                'min_order_amount' => (float) ($zone->min_order_amount ?? 0),
            ],
        ]);
    }
}

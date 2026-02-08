<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\GeoHelper;
use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\DriverNavigationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class NavigationController extends Controller
{
    public function route(DriverNavigationRequest $request): JsonResponse
    {
        try {
            $originLat = (float) $request->input('origin_lat');
            $originLng = (float) $request->input('origin_lng');
            $destLat = (float) $request->input('destination_lat');
            $destLng = (float) $request->input('destination_lng');

            $distanceKm = GeoHelper::calculateDistance($originLat, $originLng, $destLat, $destLng);
            $averageSpeedKmh = 30;
            $etaMinutes = $distanceKm > 0 ? (int) ceil(($distanceKm / $averageSpeedKmh) * 60) : 0;

            $data = [
                'distance_km' => round($distanceKm, 2),
                'eta_minutes' => $etaMinutes,
                'polyline' => [
                    ['lat' => $originLat, 'lng' => $originLng],
                    ['lat' => $destLat, 'lng' => $destLng],
                ],
                'note' => 'Straight-line route estimate. Integrate maps provider for turn-by-turn directions.',
            ];

            return ShopittPlus::response(true, 'Route generated successfully', 200, $data);
        } catch (\Exception $e) {
            Log::error('DRIVER NAVIGATION ROUTE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to generate route', 500);
        }
    }
}

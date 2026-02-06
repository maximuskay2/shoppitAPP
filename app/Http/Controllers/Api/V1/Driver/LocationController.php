<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\DriverLocationUpdateRequest;
use App\Modules\Commerce\Events\DriverLocationUpdated;
use App\Modules\Commerce\Models\Order;
use App\Modules\User\Models\DriverLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    public function store(DriverLocationUpdateRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            $location = DriverLocation::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'user_id' => $userId,
                'lat' => $request->input('lat'),
                'lng' => $request->input('lng'),
                'bearing' => $request->input('bearing'),
                'recorded_at' => $request->input('recorded_at') ?? now(),
            ]);

            $activeOrder = Order::where('driver_id', $userId)
                ->whereIn('status', ['READY_FOR_PICKUP', 'PICKED_UP', 'OUT_FOR_DELIVERY'])
                ->latest('updated_at')
                ->first();

            event(new DriverLocationUpdated($location, $activeOrder?->id));

            return ShopittPlus::response(true, 'Location updated successfully', 200, [
                'id' => $location->id,
                'recorded_at' => $location->recorded_at,
            ]);
        } catch (\Exception $e) {
            Log::error('DRIVER LOCATION UPDATE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update location', 500);
        }
    }
}

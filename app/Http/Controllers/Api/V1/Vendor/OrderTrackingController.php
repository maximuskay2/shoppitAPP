<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Commerce\Models\Order;
use App\Modules\User\Models\DriverLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderTrackingController extends Controller
{
    public function track(string $orderId): JsonResponse
    {
        try {
            $order = Order::query()
                ->where('id', $orderId)
                ->where('vendor_id', Auth::id())
                ->first();

            if (!$order) {
                return ShopittPlus::response(false, 'Order not found', 404);
            }

            $driverLocation = null;
            $driverSummary = null;
            if ($order->driver_id) {
                $order->load('driver');
                if ($order->driver) {
                    $driverSummary = [
                        'id' => $order->driver->id,
                        'name' => $order->driver->name,
                        'avatar' => $order->driver->avatar,
                    ];
                }
                $latest = DriverLocation::query()
                    ->where('user_id', $order->driver_id)
                    ->orderByDesc('recorded_at')
                    ->first();

                if ($latest) {
                    $driverLocation = [
                        'lat' => $latest->latitude,
                        'lng' => $latest->longitude,
                        'bearing' => $latest->bearing,
                        'speed' => $latest->speed,
                        'accuracy' => $latest->accuracy,
                        'recorded_at' => $latest->recorded_at,
                    ];
                }
            }

            return ShopittPlus::response(true, 'Order tracking retrieved successfully', 200, [
                'order_id' => $order->id,
                'status' => $order->status,
                'driver_id' => $order->driver_id,
                'driver' => $driverSummary,
                'driver_location' => $driverLocation,
                'delivery_location' => [
                    'lat' => $order->delivery_latitude,
                    'lng' => $order->delivery_longitude,
                ],
                'updated_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('VENDOR ORDER TRACKING: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve tracking', 500);
        }
    }

    public function eta(string $orderId): JsonResponse
    {
        try {
            $order = Order::query()
                ->where('id', $orderId)
                ->where('vendor_id', Auth::id())
                ->first();

            if (!$order) {
                return ShopittPlus::response(false, 'Order not found', 404);
            }

            return ShopittPlus::response(true, 'Order ETA retrieved successfully', 200, [
                'order_id' => $order->id,
                'status' => $order->status,
                'eta_minutes' => null,
                'updated_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('VENDOR ORDER ETA: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve ETA', 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use App\Modules\Commerce\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PerformanceController extends Controller
{
    /**
     * Get driver performance metrics
     */
    public function summary(): JsonResponse
    {
        $driver = User::with('driver')->find(Auth::id());
        if (!$driver || !$driver->driver) {
            return ShopittPlus::response(false, 'Driver profile not found.', 404);
        }
        $driverId = $driver->id;
        $completed = Order::where('driver_id', $driverId)->where('status', 'DELIVERED')->count();
        $cancelled = Order::where('driver_id', $driverId)->where('status', 'CANCELLED')->count();
        $onTime = Order::where('driver_id', $driverId)->where('status', 'DELIVERED')->whereRaw('delivered_at <= expected_delivery_at')->count();
        $late = Order::where('driver_id', $driverId)->where('status', 'DELIVERED')->whereRaw('delivered_at > expected_delivery_at')->count();
        $acceptanceRate = $completed + $cancelled > 0 ? round($completed / ($completed + $cancelled) * 100, 2) : 0;
        return ShopittPlus::response(true, 'Driver performance metrics', 200, [
            'completed_deliveries' => $completed,
            'cancelled_deliveries' => $cancelled,
            'on_time_deliveries' => $onTime,
            'late_deliveries' => $late,
            'acceptance_rate' => $acceptanceRate,
        ]);
    }
}

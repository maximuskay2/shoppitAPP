<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AlertSummaryController extends Controller
{
    public function index(): JsonResponse
    {
        $data = [
            'stuck_orders_count' => Cache::get('alerts:stuck_orders:last_count', 0),
            'driver_location_stale_count' => Cache::get('alerts:driver_locations:last_count', 0),
            'notification_failure_rate' => Cache::get('alerts:notifications:last_rate', 0),
            'notification_failed' => Cache::get('alerts:notifications:last_failed', 0),
            'notification_total' => Cache::get('alerts:notifications:last_total', 0),
        ];

        return ShopittPlus::response(true, 'Alert summary retrieved successfully', 200, $data);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AlertStatusController extends Controller
{
    public function index(): JsonResponse
    {
        $data = [
            'notifications' => [
                'last_run' => Cache::get('alerts:notifications:last_run'),
                'last_alert_at' => Cache::get('alerts:notifications:last_alert_at'),
                'last_total' => Cache::get('alerts:notifications:last_total', 0),
                'last_failed' => Cache::get('alerts:notifications:last_failed', 0),
                'last_rate' => Cache::get('alerts:notifications:last_rate', 0),
            ],
            'stuck_orders' => [
                'last_run' => Cache::get('alerts:stuck_orders:last_run'),
                'last_alert_at' => Cache::get('alerts:stuck_orders:last_alert_at'),
                'last_count' => Cache::get('alerts:stuck_orders:last_count', 0),
                'last_oldest_created_at' => Cache::get('alerts:stuck_orders:last_oldest_created_at'),
            ],
            'driver_locations' => [
                'last_run' => Cache::get('alerts:driver_locations:last_run'),
                'last_alert_at' => Cache::get('alerts:driver_locations:last_alert_at'),
                'last_count' => Cache::get('alerts:driver_locations:last_count', 0),
                'last_oldest_recorded_at' => Cache::get('alerts:driver_locations:last_oldest_recorded_at'),
            ],
        ];

        return ShopittPlus::response(true, 'Alert status retrieved successfully', 200, $data);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        $dbOk = false;
        $cacheOk = false;

        try {
            DB::connection()->getPdo();
            $dbOk = true;
        } catch (\Exception $e) {
            Log::warning('HEALTH DB CHECK: ' . $e->getMessage());
        }

        try {
            $key = 'health:ping';
            Cache::put($key, true, now()->addSeconds(10));
            $cacheOk = Cache::get($key) === true;
        } catch (\Exception $e) {
            Log::warning('HEALTH CACHE CHECK: ' . $e->getMessage());
        }

        return ShopittPlus::response(true, 'Health check completed', 200, [
            'db' => $dbOk,
            'cache' => $cacheOk,
            'queue_connection' => config('queue.default'),
            'time' => now()->toISOString(),
        ]);
    }
}

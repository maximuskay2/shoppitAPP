<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Commerce\Services\Driver\DriverStatsService;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StatsController extends Controller
{
    public function __construct(private readonly DriverStatsService $driverStatsService) {}

    public function summary(): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            $stats = $this->driverStatsService->summary($driver);

            return ShopittPlus::response(true, 'Driver stats retrieved successfully', 200, $stats);
        } catch (\Exception $e) {
            Log::error('DRIVER STATS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve driver stats', 500);
        }
    }
}

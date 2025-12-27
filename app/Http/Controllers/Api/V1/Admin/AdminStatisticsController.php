<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminStatisticsService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class AdminStatisticsController extends Controller
{
    /**
     * Create a new AdminStatisticsController instance.
     */
    public function __construct(
        protected AdminStatisticsService $adminStatisticsService,
    ) {}

    /**
     * Get admin dashboard statistics
     */
    public function index(): JsonResponse
    {
        try {
            $statistics = $this->adminStatisticsService->getStatistics();
            return ShopittPlus::response(true, 'Statistics retrieved successfully', 200, $statistics);
        } catch (Exception $e) {
            Log::error('ADMIN GET STATISTICS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve statistics', 500);
        }
    }
}
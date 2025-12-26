<?php

namespace App\Http\Controllers\v1\Admin;

use App\Helpers\TransactX;
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
            return TransactX::response(true, 'Statistics retrieved successfully', 200, $statistics);
        } catch (Exception $e) {
            Log::error('ADMIN GET STATISTICS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve statistics', 500);
        }
    }
}
<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Commerce\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    public function heatmap(Request $request): JsonResponse
    {
        try {
            $precision = (int) $request->input('precision', 2);
            $precision = max(1, min(3, $precision));

            $query = Order::query()
                ->whereNotNull('delivery_latitude')
                ->whereNotNull('delivery_longitude');

            if ($startDate = $request->input('start_date')) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate = $request->input('end_date')) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $points = $query
                ->selectRaw("ROUND(delivery_latitude, {$precision}) as lat, ROUND(delivery_longitude, {$precision}) as lng, COUNT(*) as count")
                ->groupBy('lat', 'lng')
                ->orderByDesc('count')
                ->get();

            return ShopittPlus::response(true, 'Heatmap data retrieved successfully', 200, [
                'precision' => $precision,
                'points' => $points,
            ]);
        } catch (\Exception $e) {
            Log::error('ADMIN ANALYTICS HEATMAP: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve heatmap data', 500);
        }
    }

    public function performance(Request $request): JsonResponse
    {
        try {
            $query = Order::query()->whereIn('status', ['DELIVERED', 'COMPLETED']);

            if ($startDate = $request->input('start_date')) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate = $request->input('end_date')) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $stats = $query->select([
                DB::raw('COUNT(*) as total_deliveries'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, assigned_at, picked_up_at)) as avg_pickup_minutes'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, picked_up_at, delivered_at)) as avg_delivery_minutes'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, assigned_at, delivered_at)) as avg_end_to_end_minutes'),
            ])->first();

            $statusBreakdown = Order::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();

            return ShopittPlus::response(true, 'Performance analytics retrieved successfully', 200, [
                'summary' => $stats,
                'status_breakdown' => $statusBreakdown,
            ]);
        } catch (\Exception $e) {
            Log::error('ADMIN ANALYTICS PERFORMANCE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve performance analytics', 500);
        }
    }
}

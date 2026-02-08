<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Commerce\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EarningController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $vendor = $user->vendor;

            $totalOrders = Order::query()->where('vendor_id', $vendor->id)->count();
            $totalEarnings = $vendor->settlements()->sum('vendor_amount');
            $todayEarnings = $vendor->settlements()->whereDate('created_at', today())->sum('vendor_amount');

            return ShopittPlus::response(true, 'Vendor earnings retrieved successfully', 200, [
                'total_orders' => $totalOrders,
                'total_earnings' => $totalEarnings,
                'today_earnings' => $todayEarnings,
            ]);
        } catch (\Exception $e) {
            Log::error('VENDOR EARNINGS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve earnings', 500);
        }
    }
}

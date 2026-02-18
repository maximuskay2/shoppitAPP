<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Product;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorAnalyticsController extends Controller
{
    /**
     * Get vendor analytics: sales trends, top products
     */
    public function summary(): JsonResponse
    {
        $user = User::find(Auth::id());
        $vendorId = $user->vendor->id ?? null;
        if (!$vendorId) {
            return ShopittPlus::response(false, 'Not a vendor', 403);
        }

        // Sales trends: orders per month (last 6 months)
        $salesTrends = Order::where('vendor_id', $vendorId)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as orders, SUM(total_amount) as revenue')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get();

        // Top products by sales (last 6 months)
        $topProducts = Product::where('vendor_id', $vendorId)
            ->withCount(['lineItems as sales_count' => function ($q) {
                $q->select(DB::raw('count(*)'));
            }])
            ->orderByDesc('sales_count')
            ->limit(5)
            ->get(['id', 'name']);

        return ShopittPlus::response(true, 'Vendor analytics summary', 200, [
            'sales_trends' => $salesTrends,
            'top_products' => $topProducts,
        ]);
    }
}

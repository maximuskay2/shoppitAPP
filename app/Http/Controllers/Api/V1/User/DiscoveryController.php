<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Modules\Commerce\Models\Product;
use App\Modules\User\Models\Vendor;
use Illuminate\Http\Request;

class DiscoveryController extends Controller
{
    public function nearbyVendors(Request $request)
    {
        $user = auth()->user();

        // Filter vendors by user's state and city
        $vendors = Vendor::with('user')
            ->whereHas('user', function ($query) use ($user) {
                $query->where('state', $user->state)
                      ->where('city', $user->city);
            })
            ->paginate(20);

        return response()->json($vendors);
    }

    public function newProducts(Request $request)
    {
        $days = $request->get('days', 30); // Allow custom days, default 30
        $perPage = $request->get('per_page', 20);

        $products = Product::with('vendor.user')
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($products);
    }
}
<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Commerce\ProductResource;
use App\Http\Resources\User\VendorResource;
use App\Modules\Commerce\Models\Product;
use App\Modules\Commerce\Models\Waitlist;
use App\Modules\User\Models\SearchHistory;
use App\Modules\User\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscoveryController extends Controller
{
    public function nearbyVendors(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Log the search
        SearchHistory::create([
            'user_id' => $user->id,
            'search_query' => "Nearby vendors in {$user->state}, {$user->city}",
        ]);

        // Filter vendors by user's state and city
        $vendors = Vendor::with('user')
            ->whereHas('user', function ($query) use ($user) {
                $query->where('state', $user->state)
                      ->where('city', $user->city);
            })
            ->paginate(20);

        $canJoinWaitlist = $vendors->isEmpty();
        $data = [
            'vendors' => VendorResource::collection($vendors),
            'can_join_waitlist' => $canJoinWaitlist,
        ];

        return ShopittPlus::response(true, 'Nearby vendors retrieved successfully', 200, $data);
    }

    public function newProducts(Request $request): JsonResponse
    {
        $user = auth()->user();
        $days = $request->get('days', 30); // Allow custom days, default 30
        $perPage = $request->get('per_page', 20);

        // Log the search
        SearchHistory::create([
            'user_id' => $user->id,
            'search_query' => "New products (last {$days} days)",
        ]);

        $products = Product::with('vendor.user')
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return ShopittPlus::response(true, 'New products retrieved successfully', 200, ProductResource::collection($products));
    }

    public function joinWaitlist(Request $request): JsonResponse
    {
        $user = auth()->user();

        Waitlist::firstOrCreate([
            'user_id' => $user->id,
            'state' => $user->state,
            'city' => $user->city,
        ]);

        return ShopittPlus::response(true, 'Successfully joined the waitlist', 200);
    }

    public function recentSearches(Request $request): JsonResponse
    {
        $user = auth()->user();
        $limit = $request->get('limit', 10);

        $searches = SearchHistory::where('user_id', $user->id)
            ->orderBy('searched_at', 'desc')
            ->limit($limit)
            ->get(['search_query', 'searched_at']);

        return ShopittPlus::response(true, 'Recent searches retrieved successfully', 200, $searches);
    }
}
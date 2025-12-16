<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Commerce\ProductResource;
use App\Http\Resources\User\VendorResource;
use App\Modules\Commerce\Models\Product;
use App\Modules\Commerce\Models\Waitlist;
use App\Modules\Transaction\Enums\UserSubscriptionStatusEnum;
use App\Modules\User\Models\SearchHistory;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscoveryController extends Controller
{
    public function nearbyVendors(Request $request): JsonResponse
    {
        $user = User::find(Auth::id());
        $address = $user->addresses()->where('is_active', true)->first();

        // Filter vendors by user's state and city, ordered by subscription plan key (3 first, then 2, then 1)
        $vendors = Vendor::with(['user', 'subscription.plan'])
            ->whereHas('subscription', function ($query) {
                $query->where('status', UserSubscriptionStatusEnum::ACTIVE);
            })
            ->whereHas('user', function ($query) use ($address) {
                $query->where('state', $address->state)
                      ->where('city', $address->city);
            })
            ->join('subscriptions', 'vendors.id', '=', 'subscriptions.vendor_id')
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->orderByRaw('CASE 
                WHEN subscription_plans.key = 3 THEN 1 
                WHEN subscription_plans.key = 2 THEN 2 
                ELSE 3 
            END')
            ->select('vendors.*')
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
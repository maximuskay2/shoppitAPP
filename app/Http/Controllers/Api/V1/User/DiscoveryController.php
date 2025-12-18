<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Commerce\ProductResource;
use App\Http\Resources\Commerce\SingleProductResource;
use App\Http\Resources\Commerce\VendorResource;
use App\Modules\Commerce\Models\Product;
use App\Modules\Commerce\Models\Waitlist;
use App\Modules\Commerce\Services\DiscoveryService;
use App\Modules\Transaction\Enums\UserSubscriptionStatusEnum;
use App\Modules\User\Models\SearchHistory;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscoveryController extends Controller
{
    public function __construct(private readonly DiscoveryService $discoveryService) {}

    public function nearbyVendors(Request $request): JsonResponse
    {
        $user = User::find(Auth::id());
        
        $vendors = $this->discoveryService->getNearbyVendors($user);

        $data = [
            'data' => VendorResource::collection($vendors->items()),
            'next_cursor' => $vendors->nextCursor()?->encode(),
            'prev_cursor' => $vendors->previousCursor()?->encode(),
            'has_more' => $vendors->hasMorePages(),
            'per_page' => $vendors->perPage(),
        ];
        return ShopittPlus::response(true, 'Nearby vendors retrieved successfully', 200, $data);
    }

    public function nearbyProducts(): JsonResponse
    {
        $user = User::find(Auth::id());
        
        $products = $this->discoveryService->getNearbyProducts($user);

        $data = [
            'data' => ProductResource::collection($products->items()),
            'next_cursor' => $products->nextCursor()?->encode(),
            'prev_cursor' => $products->previousCursor()?->encode(),
            'has_more' => $products->hasMorePages(),
            'per_page' => $products->perPage(),
        ];
        return ShopittPlus::response(true, 'Nearby products retrieved successfully', 200, $data);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $user = User::find(Auth::id());

        $products = $this->discoveryService->searchProducts($user, $request);
        
        $data = [
            'data' => ProductResource::collection($products->items()),
            'next_cursor' => $products->nextCursor()?->encode(),
            'prev_cursor' => $products->previousCursor()?->encode(),
            'has_more' => $products->hasMorePages(),
            'per_page' => $products->perPage(),
        ];        
        return ShopittPlus::response(true, 'Search results retrieved successfully', 200, $data);
    }

    public function searchVendors(Request $request): JsonResponse
    {
        $user = User::find(Auth::id());

        $vendors = $this->discoveryService->searchVendors($user, $request);
        
        $data = [
            'data' => VendorResource::collection($vendors->items()),
            'next_cursor' => $vendors->nextCursor()?->encode(),
            'prev_cursor' => $vendors->previousCursor()?->encode(),
            'has_more' => $vendors->hasMorePages(),
            'per_page' => $vendors->perPage(),
        ];
        return ShopittPlus::response(true, 'Search results retrieved successfully', 200, $data);
    }

    public function joinWaitlist(Request $request): JsonResponse
    {
        $user = User::find(Auth::id());

        Waitlist::firstOrCreate([
            'user_id' => $user->id,
            'state' => $user->state,
            'city' => $user->city,
        ]);

        return ShopittPlus::response(true, 'Successfully joined the waitlist', 200);
    }

    public function recentSearches(Request $request): JsonResponse
    {
        $user = User::find(Auth::id());
        $limit = $request->get('limit', 10);

        $searches = SearchHistory::where('user_id', $user->id)
            ->orderBy('searched_at', 'desc')
            ->get(['search_query', 'searched_at']);

        return ShopittPlus::response(true, 'Recent searches retrieved successfully', 200, $searches);
    }

    public function productDetails(string $productId): JsonResponse
    {
        $product = Product::with('reviews')->find($productId);

        if (!$product) {
            return ShopittPlus::response(false, 'Product not found', 404);
        }

        return ShopittPlus::response(true, 'Product details retrieved successfully', 200, new SingleProductResource($product));
    }

    public function vendorDetails(string $vendorId): JsonResponse
    {
        $vendor = Vendor::with('reviews')->find($vendorId);

        if (!$vendor) {
            return ShopittPlus::response(false, 'Vendor not found', 404);
        }

        return ShopittPlus::response(true, 'Vendor details retrieved successfully', 200, new VendorResource($vendor));
    }
}
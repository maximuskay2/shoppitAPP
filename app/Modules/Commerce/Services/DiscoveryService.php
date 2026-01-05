<?php

namespace App\Modules\Commerce\Services;

use App\Modules\Commerce\Models\Product;
use App\Modules\Commerce\Models\Settings;
use App\Modules\Transaction\Enums\UserSubscriptionStatusEnum;
use App\Modules\User\Enums\UserKYBStatusEnum;
use App\Modules\User\Models\SearchHistory;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Brick\Money\Money;

class DiscoveryService
{
    public function getNearbyVendors(User $user)
    {
        $query = $this->queryVendors($user);

        $vendors = $query
            ->select('vendors.*')
            ->cursorPaginate(20);

        return $vendors;
    }

    public function getNearbyProducts(User $user)
    {
        $vendorsQuery = $this->queryVendors($user);

        $products = Product::with(['vendor.user'])
            ->whereHas('vendor', function ($vendorQuery) use ($vendorsQuery) {
                $vendorQuery->whereIn('vendors.id', $vendorsQuery->pluck('vendors.id'));
            })
            ->where('is_available', true)
            ->inRandomOrder()
            ->cursorPaginate(20);

        return $products;
    }

    public function searchProducts(User $user, object $request)
    {
        $searchQuery = strtolower($request->get('query', ''));
        $limit = intval($request->get('limit', 20));
        $from = intval($request->get('from'));
        $to = intval($request->get('to'));
        $vendor_id = $request->get('vendor_id');
        $currency = Settings::where('name', 'currency')->first()->value;
        $vendorsQuery = $this->queryVendors($user);

        $products = Product::with(['vendor.user', 'reviews'])
            ->whereHas('vendor', function ($vendorQuery) use ($vendorsQuery) {
                $vendorQuery->whereIn('vendors.id', $vendorsQuery->pluck('vendors.id'));
            })
            ->when($searchQuery, function ($query) use ($searchQuery) {
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('name', 'ILIKE', "%{$searchQuery}%")
                    ->orWhere('description', 'ILIKE', "%{$searchQuery}%");
                });
            })
            ->when($vendor_id, function ($query) use ($vendor_id) {
                $query->where('vendor_id', $vendor_id);
            })
            ->when($from && $to, function ($query) use ($from, $to, $currency) {
                $query->whereBetween('price', [Money::of($from, $currency)->getMinorAmount()->toInt(), Money::of($to, $currency)->getMinorAmount()->toInt()]);
            })
            ->orderBy('created_at', 'desc')
            ->cursorPaginate($limit);

        // Log the search query
        if ($searchQuery) {
            $this->createSearchHistory($user, $searchQuery);
        }

        return $products;
    }

    public function searchVendors(User $user, object $request)
    {
        $searchQuery = strtolower($request->get('query', ''));
        $free_delivery = boolval($request->get('free_delivery'));
        $favourite = boolval($request->get('favourite'));
        $limit = intval($request->get('limit', 20));

        $favouriteVendorIds = [];
        if ($favourite) {
            $favouriteVendorIds = $user->favourites()
                ->where('favouritable_type', 'App\Modules\User\Models\Vendor')
                ->pluck('favouritable_id')
                ->toArray();
        }

        $vendorsQuery = $this->queryVendors($user);
        $vendors = $vendorsQuery
            ->when($searchQuery, function ($query) use ($searchQuery) {
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('vendors.business_name', 'ILIKE', "%{$searchQuery}%");
                });
            })
            ->when($free_delivery, function ($query) use ($free_delivery) {
                if ($free_delivery) {
                    $query->where('vendors.delivery_fee', 0);
                } else {
                    $query->where('vendors.delivery_fee', '>', 0);
                }
            })
            ->when($favourite && count($favouriteVendorIds) > 0, function ($query) use ($favouriteVendorIds) {
                $query->whereIn('vendors.id', $favouriteVendorIds);
            })
            ->select('vendors.*')
            ->cursorPaginate($limit);

        // Log the search query
        if ($searchQuery) {
            $this->createSearchHistory($user, $searchQuery);
        }

        return $vendors;
    }

    private function createSearchHistory(User $user, string $searchQuery): void
    {
        if ($user->searches()->where('search_query', $searchQuery)->exists()) {
            $user->searches()->where('search_query', $searchQuery)->update([
                'searched_at' => now(),
            ]);
            return;
        }

        if ($user->searches()->count() >= 10) {
            $oldestSearch = $user->searches()->orderBy('searched_at', 'asc')->first();
            $oldestSearch->delete();
        }

        $user->searches()->create([
            'search_query' => $searchQuery,
            'searched_at' => now(),
        ]);
    }

    private function queryVendors(User $user)
    {
        $address = $user->addresses()->where('is_active', true)->first();

        // Filter vendors by user's state and city, ordered by subscription plan key (3 first, then 2, then 1)
        return Vendor::with(['user', 'subscription.plan', 'products'])
            ->where('kyb_status', UserKYBStatusEnum::SUCCESSFUL)
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
            END');
    }
}
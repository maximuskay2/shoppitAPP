<?php

namespace App\Http\Middleware\Subscription;

use App\Helpers\ShopittPlus;
use App\Helpers\ShopittPlus;
use App\Models\User;
use App\Modules\Transaction\Models\Subscription;
use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\User\Models\Vendor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductListing
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return ShopittPlus::response(false, 'Unauthenticated.', 401);
        }

        if (is_null($user->vendor)) {
            return ShopittPlus::response(false, 'User is not a vendor.', 403);
        }

        if ($user->vendor->subscription->plan->key === 1) {
            $limit = SubscriptionPlan::FREE_PLAN_PRODUCT_LISTING_LIMIT;
        } else if ($user->vendor->subscription->plan->key === 2) {
            $limit = SubscriptionPlan::GROWTH_PLAN_PRODUCT_LISTING_LIMIT;
        } else {
            return $next($request);
        }

        $count = $user->vendor->products->count();
        
        if ($limit == 0 || $count >= $limit) {
            return ShopittPlus::response(false, 'You are not allowed to proceed due to your subscription plan.', 403);
        }

        return $next($request);
    }
}

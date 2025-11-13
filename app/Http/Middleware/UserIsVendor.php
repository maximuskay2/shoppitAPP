<?php

namespace App\Http\Middleware;

use App\Helpers\ShopittPlus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserIsVendor
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
        return $next($request);
    }
}

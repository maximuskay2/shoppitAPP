<?php

namespace App\Http\Middleware;

use App\Helpers\ShopittPlus;
use App\Modules\User\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserIsSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $admin = Admin::find($user?->id);

        if (!$admin) {
            return ShopittPlus::response(false, 'Unauthorized. Admin access required.', 401);
        }
        
        if (!$admin->is_super_admin) {
            return ShopittPlus::response(false, 'Access denied. Super admin privileges required.', 403);
        }
        return $next($request);
    }
}

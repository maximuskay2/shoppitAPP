<?php

namespace App\Http\Middleware;

use App\Helpers\ShopittPlus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBlogManagementScope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin-api');

        // Check if admin is authenticated
        if (!$admin) {
            return ShopittPlus::response(false, 'Unauthorized', 401);
        }

        // Super admin bypasses scope check
        if ($admin->is_super_admin ?? false) {
            return $next($request);
        }

        // Check if admin has permissions
        if (!$admin->permissions) {
            return ShopittPlus::response(false, 'Access denied. No permissions assigned.', 403);
        }

        // Get permissions array
        $permissions = is_array($admin->permissions) 
            ? $admin->permissions 
            : json_decode($admin->permissions, true);

        // Check if admin has wildcard privilege or blog-management scope
        if (in_array('*', $permissions) || in_array('blog-management', $permissions)) {
            return $next($request);
        }

        return ShopittPlus::response(false, 'Access denied. Blog management privilege required.', 403);
    }
}

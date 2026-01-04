<?php

namespace App\Http\Middleware;

use App\Helpers\ShopittPlus;
use App\Modules\User\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin-api');

        $admin = Admin::find($admin?->id);        

        if (!$admin) {
            return ShopittPlus::response(false, 'Unauthorized. Admin access required.', 401);
        }
        
        return $next($request);
    }
}

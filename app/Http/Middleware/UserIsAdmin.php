<?php

namespace App\Http\Middleware;

use App\Helpers\ShopittPlus;
use App\Modules\User\Models\Admin;
use Closure;
use Illuminate\Http\Request;
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
        $user = $request->user();

        $admin = Admin::find($user?->id);        

        if (!$admin) {
            return ShopittPlus::response(false, 'Unauthorized. Admin access required.', 401);
        }
        
        return $next($request);
    }
}

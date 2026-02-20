<?php

namespace App\Http\Middleware;

use App\Helpers\ShopittPlus;
use App\Modules\User\Enums\UserStatusEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserIsActive
{
    /**
     * Handle an incoming request.
     * Only BLOCKED and SUSPENDED (admin-triggered) show the suspended message.
     * NEW drivers are allowed through for onboarding (documents, profile) after OTP verification.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return ShopittPlus::response(false, 'Unauthenticated', 401);
        }

        $status = $user->status;
        $blocked = $status === UserStatusEnum::BLOCKED;
        $suspended = $status === UserStatusEnum::SUSPENDED;

        if ($blocked) {
            return ShopittPlus::response(false, 'Your account has been blocked. Please contact support.', 403);
        }

        if ($suspended) {
            return ShopittPlus::response(false, 'Your account has been temporarily suspended. Please contact support.', 403);
        }

        return $next($request);
    }
}

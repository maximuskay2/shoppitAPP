<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class RefreshTokenController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $refreshToken = $data['refresh_token'];
        $token = PersonalAccessToken::findToken($refreshToken);

        if (!$token || !$token->can('refresh')) {
            return ShopittPlus::response(false, 'Invalid refresh token', 401);
        }

        $user = $token->tokenable;
        if (!$user instanceof User) {
            return ShopittPlus::response(false, 'Invalid refresh token', 401);
        }

        $accessToken = $user->createToken('auth_token')->plainTextToken;

        return ShopittPlus::response(true, 'Token refreshed', 200, [
            'token' => $accessToken,
            'refresh_token' => $refreshToken,
        ]);
    }
}

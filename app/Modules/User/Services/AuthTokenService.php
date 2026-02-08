<?php

namespace App\Modules\User\Services;

use App\Modules\User\Models\User;

class AuthTokenService
{
    public function createTokensForUser(User $user): array
    {
        $user->tokens()->where('name', 'refresh_token')->delete();

        return [
            'token' => $user->createToken('auth_token')->plainTextToken,
            'refresh_token' => $user->createToken('refresh_token', ['refresh'])->plainTextToken,
        ];
    }
}

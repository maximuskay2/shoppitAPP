<?php

namespace App\Modules\User\Services;

use App\Modules\User\Models\User;

class DeviceTokenService
{

    /**
     * Save distinct token for user
     * @param User $user
     * @param string $token
     * @return void
     */
    public function saveDistinctTokenForUser(User $user, string $token): void
    {
        $existingToken = $user->deviceTokens()->where('token', $token)->first();

        // If the token doesn't exist, save it for the user
        if (!$existingToken) {
            $user->deviceTokens()->create(['token' => $token]);
        }
    }
}

<?php

namespace App\Modules\User\Actions;

use App\Helpers\ShopittPlus;
use App\Modules\User\Data\Auth\LoginDTO;
use App\Modules\User\Models\User;
use App\Modules\User\Services\DeviceTokenService;
use Illuminate\Support\Facades\Hash;

class LoginAction
{
    public static function execute(LoginDTO $dto)
    {
        $user = User::where('email', $dto->email)->first();
        if (!$user || !Hash::check($dto->password, $user->password)) {
            return ShopittPlus::response(false, 'Invalid credentials', 401);
        }
        
        $token = $user->createToken('auth_token')->plainTextToken;

        if (!is_null($dto->fcm_device_token)) {
            resolve(DeviceTokenService::class)
                ->saveDistinctTokenForUser($user, $dto->fcm_device_token);
        }

        $role = 'user';
        if ($user->driver) {
            $role = 'driver';
        } elseif ($user->vendor) {
            $role = 'vendor';
        }

        return ShopittPlus::response(true, 'Login successful', 200, [
            'token' => $token,
            'role' => $role,
        ]);
    }
}

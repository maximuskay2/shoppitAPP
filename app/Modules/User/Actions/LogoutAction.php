<?php

namespace App\Modules\User\Actions;

use App\Helpers\ShopittPlus;

class LogoutAction
{
    public static function execute($user)
    {
        $user->tokens()->delete();
        return ShopittPlus::response(true, 'Logged out successfully', 200);
    }
}
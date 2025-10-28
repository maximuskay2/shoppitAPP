<?php

namespace App\Modules\User\Services\Admin;

use App\Modules\User\Models\Admin;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

class AdminService
{
    /**
     * Find user by id
     * 
     * @param string $user_id
     * @return Admin|null
     */
    public function getUserById($user_id)
    {
        return Admin::find($user_id);
    }


    /**
     * Update admin account
     * 
     * @param Admin|Authenticatable $admin
     * @param array $attributes
     * @return Admin
     */
    public function updateAdminAccount($admin, $attributes)
    {
        $admin->update([
            'name' => $attributes['name'] ?? $admin->name,
            'password' => $attributes['password'] ?? $admin->password,
            'avatar' => $attributes['avatar'] ?? $admin->avatar,
            'phone' => $attributes['phone'] ?? $admin->phone,
            'phone_verified_at' => $attributes['phone_verified_at'] ?? $admin->phone_verified_at,
            'email_verified_at' => $attributes['email_verified_at'] ?? $admin->email_verified_at,
            'two_factor_secret' => $attributes['two_factor_secret'] ?? $admin->two_factor_secret,
            'two_factor_recovery_codes' => $attributes['two_factor_recovery_codes'] ?? $admin->two_factor_recovery_codes,
            'two_factor_confirmed_at' => $attributes['two_factor_confirmed_at'] ?? $admin->two_factor_confirmed_at,
        ]);

        return $admin;
    }
}

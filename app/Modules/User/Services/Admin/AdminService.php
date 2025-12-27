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
            'email_verified_at' => $attributes['email_verified_at'] ?? $admin->email_verified_at,
        ]);

        return $admin;
    }
}

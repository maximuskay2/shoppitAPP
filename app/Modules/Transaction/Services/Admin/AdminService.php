<?php

namespace App\Services\Admin;

use App\Models\Business\SubscriptionModel;
use App\Models\Admin;
use App\Notifications\User\BVNVerificationStatusNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

class AdminService
{
    /**
     * Find admin by id
     *
     * @param string $admin_id
     * @return Admin|null
     */
    public function getUserById($admin_id)
    {
        return Admin::find($admin_id);
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
        $updateData = [];

        // Handle name 
        if (isset($attributes['name'])) {
            $updateData['name'] = $attributes['name'];
        }

        // Handle other fields
        if (isset($attributes['email'])) {
            $updateData['email'] = $attributes['email'];
        }

        if (!empty($updateData)) {
            $admin->update($updateData);
        }

        return $admin;
    }

    /**
     * Change admin password
     *
     * @param Admin|Authenticatable $admin
     * @param string $newPassword
     * @return Admin
     */
    public function changeAdminPassword($admin, $newPassword)
    {
        $admin->update([
            'password' => bcrypt($newPassword),
        ]);

        return $admin;
    }
}

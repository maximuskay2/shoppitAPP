<?php

namespace App\Modules\User\Services;

// use App\Events\User\UserAccountUpdated;

use App\Models\Business\SubscriptionModel;
use App\Modules\User\Models\User;
use App\Notifications\User\BVNVerificationStatusNotification;
use App\Http\Resources\User\UserResource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserService
{
    /**
     * Generates a 6 character code
     * @return string $random_code
     */
    public function generateRandomCode(): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $random_code = '';
        for ($i = 0; $i < 6; $i++) {
            $random_code .= $characters[rand(0, $charactersLength - 1)];
        }
        return $random_code;
    }

    /**
     * Get user by referral_code
     * @param string $referral_code
     * @param mixed $column
     */
    public function getUserByRefCode(string $referral_code, $column)
    {
        return is_array($column) ?
            User::select($column)->where('referral_code', $referral_code)->first() :
            User::select($column)->where('referral_code', $referral_code)->first()?->$column;
    }


    /**
     * Find user by id
     * 
     * @param string $user_id
     * @return User|null
     */
    public function getUserById($user_id)
    {
        return User::find($user_id);
    }


    /**
     * Find user by customer code
     * 
     * @param string $customer_code
     * @return User|null
     */
    public function getUserByCustomerCode($customer_code)
    {
        return User::where('customer_code', $customer_code)->first();
    }


    /**
     * Update user account
     * 
     * @param User|Authenticatable $user
     * @param array $attributes
     * @return User
     */
    public function updateUserAccount($user, $attributes)
    {
        $updates = [
            'name' => $attributes['name'] ?? $user->name,
            'username' => $attributes['username'] ?? $user->username,
            'email' => $attributes['email'] ?? $user->email,
            'phone' => $attributes['phone'] ?? $user->phone,
            'address' => $attributes['address'] ?? $user->address,
            'state' => $attributes['state'] ?? $user->state,
            'city' => $attributes['city'] ?? $user->city,
            'address_2' => $attributes['address_2'] ?? $user->address_2,
            'country' => $attributes['country'] ?? $user->country,
            'avatar' => $attributes['avatar'] ?? $user->avatar,
            'kyc_status' => $attributes['kyc_status'] ?? $user->kyc_status,
            'push_in_app_notifications' => $attributes['push_in_app_notifications'] ?? $user->push_in_app_notifications,
            'last_logged_in_at' => $attributes['last_logged_in_at'] ?? $user->last_logged_in_at,
            'last_logged_in_device' => $attributes['last_logged_in_device'] ?? $user->last_logged_in_device,
            'email_verified_at' => $attributes['email_verified_at'] ?? $user->email_verified_at,
            'referred_by_user_id' => $attributes['referred_by_user_id'] ?? $user->referred_by_user_id,
            'password' => $attributes['password'] ?? $user->password,
        ];

        $user->update($updates);
        $user->refresh();

        return $user;
    }

    /**
     * Update sub account
     * 
     * @param User|Authenticatable $user
     * @param array $attributes
     * @return User
     */
    // public function updateSubAccount($user, $attributes)
    // {
    //     $user->update([
    //         'name' => $attributes['name'] ?? $user->name,
    //         'username' => $attributes['username'] ?? $user->username,
    //         'password' => $attributes['password'] ?? $user->password,
    //     ]);

    //     $user->refresh();

    //     // event(new UserAccountUpdated($user));

    //     return $user;
    // }

    /**
     * delete sub account
     * 
     * @param User|Authenticatable $user
     * @param array $attributes
     * @return bool
     */
    // public function deleteSubAccount($user)
    // {
    //     return $user->delete();
    // }

    // public function revertSubAccounts(User $user, SubscriptionModel $model) {
    //     $features = json_decode($model->features);
    //     $count = User::where('main_account_id', $user->id)
    //         ->count();

    //     $difference = $count - $features->sub_accounts->limit;
    //     if ($difference <= 0) {
    //         return;
    //     }

    //     $subbAccounts = User::where('main_account_id', $user->id)
    //         ->latest()
    //         ->take($difference)
    //         ->get();

    //     foreach ($subbAccounts as $subAccount) {
    //         $subAccount->delete();
    //     }
    // }

    /**
     * @Param Request $request
     * @return $user
     */
    public function getAuthenticatedUser(Request $request) {
        // retrieve all neccessary info here
        // retrieving basic user info for now
        Log::info('Customize user response body');
        $user = new UserResource($request->user());
        return $user;
    }
}

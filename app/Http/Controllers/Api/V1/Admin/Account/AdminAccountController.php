<?php

namespace App\Http\Controllers\v1\Admin\Account;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Account\ChangeAdminPasswordRequest;
use App\Http\Requests\Admin\Account\UpdateAdminAccountRequest;
use App\Http\Requests\User\Account\UpdateUserAvatarRequest;
use App\Models\Admin;
use App\Services\Admin\AdminService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AdminAccountController extends Controller
{

    /**
     * Create a new AdminAccountController instance.
     */
    public function __construct(
        protected AdminService $adminService,
    ) {
    }


    /**
     * Return the admin account
     */
    public function show(): JsonResponse
    {
        try {
            // $user = Auth::guard('admin')->user();
            $user = request()->user('admin-api');

            if (!$user) {
                return TransactX::response(false, 'Admin not authenticated', 401);
            }

            $user = $this->adminService->getUserById($user->id);

            if (!$user) {
                return TransactX::response(false, 'Admin not found', 404);
            }

            return TransactX::response(true, 'Admin account retrieved successfully', 200, (object) ["user" => $user]);
        } catch (Exception $e) {
            Log::error('GET ADMIN ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve admin account', 500);
        }
    }
    
    /**
     * Update user account (profile)
     */
    public function update(UpdateAdminAccountRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $user = Auth::guard('admin-api')->user();
            $user = $this->adminService->updateAdminAccount($user, $validatedData);

            return TransactX::response(true, 'Profile updated successfully', 200, (object) ["user" => $user]);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE ADMIN ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPDATE ADMIN ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to update admin profile', 500);
        }
    }
    
    /**
     * Update user avatar
     */
    public function updateAvatar(UpdateUserAvatarRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            
            $user = Auth::guard('admin-api')->user();
            $uploadedFile = $validatedData['avatar'];
            $result = cloudinary()->upload($uploadedFile->getRealPath())->getSecurePath();
            $data = [
                'avatar' =>  $result
            ];

            $user = $this->adminService->updateAdminAccount($user, $data);
            
            return TransactX::response(true, 'User avatar updated successfully', 200, (object) ["user" => $user]);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE ADMIN AVATAR: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPDATE ADMIN AVATAR: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to update admin avatar', 500);
        }
    }

    /**
     * Change admin password
     */
    public function changePassword(ChangeAdminPasswordRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $user = Auth::guard('admin-api')->user();

            // Verify current password
            if (!Hash::check($validatedData['current_password'], $user->password)) {
                return TransactX::response(false, 'Current password is incorrect', 400);
            }

            $user = $this->adminService->changeAdminPassword($user, $validatedData['new_password']);

            return TransactX::response(true, 'Password changed successfully', 200);
        } catch (Exception $e) {
            Log::error('CHANGE ADMIN PASSWORD: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to change password', 500);
        }
    }
}

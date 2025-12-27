<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Modules\User\Models\Admin;
use App\Modules\User\Models\VerificationCode;
use App\Modules\User\Services\Admin\AdminService;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AdminResetPasswordController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ResetPasswordRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $admin = Admin::where('email', $validatedData['email'])->first();

            if (!$admin) {
                return ShopittPlus::response(false, 'Admin not found', 404);
            }

            $verification_code = VerificationCode::where('email', $admin->email)
                ->where('purpose', 'verification')
                ->where('is_verified', true)
                ->first();

            if (!$verification_code) {
                throw new InvalidArgumentException("Verification code has not been verified");
            }

            $verification_code->delete();

            $adminService = resolve(AdminService::class);
            $admin = $adminService->updateAdminAccount($admin, [
                'password' => $validatedData['password'],
            ]);

            return ShopittPlus::response(true, 'Password changed successfully', 200);
        } catch (Exception $e) {
            Log::error('LOGIN ADMIN: Error Encountered: ' . $e->getMessage());

            return ShopittPlus::response(false, $e->getMessage(), 500);
        }
    }
}

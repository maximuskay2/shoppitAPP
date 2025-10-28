<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Modules\User\Models\User;
use App\Modules\User\Models\VerificationCode;
use App\Modules\User\Services\UserService;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ResetPasswordController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ResetPasswordRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $user = User::where('email', $validatedData['email'])->first();

            if (!$user) {
                return ShopittPlus::response(false, 'User not found', 404);
            }

            $verification_code = VerificationCode::where('email', $user->email)
                ->where('purpose', 'verification')
                ->where('is_verified', true)
                ->first();

            if (!$verification_code) {
                throw new InvalidArgumentException("Verification code has not been verified");
            }

            $verification_code->delete();

            $userService = resolve(UserService::class);
            $user = $userService->updateUserAccount($user, [
                'password' => $validatedData['password'],
            ]);

            return ShopittPlus::response(true, 'Password changed successfully', 200);
        } catch (Exception $e) {
            Log::error('RESET PASSWORD: Error Encountered: ' . $e->getMessage());

            return ShopittPlus::response(false, $e->getMessage(), 500);
        }
    }
}

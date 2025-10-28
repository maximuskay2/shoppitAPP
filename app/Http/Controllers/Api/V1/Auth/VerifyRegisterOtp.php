<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Api\V1\Otp\UserOtpController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\Otp\VerifyRegisterOtpRequest;
use App\Modules\User\Events\UserCreatedEvent;
use App\Modules\User\Models\User;
use App\Modules\User\Models\VerificationCode;
use App\Modules\User\Services\UserService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class VerifyRegisterOtp extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(VerifyRegisterOtpRequest $request)
    {
        try {
            DB::beginTransaction();
            $validatedData = $request->validated();
            $user = User::where('email', $validatedData['email'])->first();

            $otpService = resolve(UserOtpController::class);
            $response = $otpService->verifyAppliedCode($validatedData['email'], $validatedData['verification_code'], null, 'verification');

            if (!$response->status) {
                throw new Exception('Failed to verify otp');
            }

            $verification_code = VerificationCode::where('email', $validatedData['email'])
                ->where('purpose', 'verification')
                ->where('is_verified', true)
                ->first();

            if (!$verification_code) {
                throw new InvalidArgumentException("Verification code has not been verified");
            }

            $verification_code->delete();

            $userService = resolve(UserService::class);
            $userService->updateUserAccount($user, [
                'email_verified_at' => Carbon::now(),
                'last_logged_in_at' => Carbon::now(),
                'last_logged_in_device' => $request->header('User-Agent'),
                'push_in_app_notifications' => true,
            ]);

            DB::commit();
            event(new UserCreatedEvent($user));

            return ShopittPlus::response(true, 'Otp has been verified', 200);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('VERIFY REGISTER OTP: Error Encountered: ' . $e->getMessage());

            return ShopittPlus::response(false, $e->getMessage(), 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Api\V1\Otp\UserOtpController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\Otp\ResendRegisterOtpRequest;
use App\Modules\User\Models\VerificationCode;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ResendRegisterOtp extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ResendRegisterOtpRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $verification_code = VerificationCode::where('email', $validatedData['email'])
                ->where('purpose', 'verification')
                ->where('is_verified', false)
                ->first();

            if (!$verification_code) {
                throw new InvalidArgumentException("Verification code has not been requested");
            }

            $verification_code->delete();

            $otpService = resolve(UserOtpController::class);
            $response = $otpService->sendForVerification(
                $validatedData['email'],
                null,
            );

            if (!$response->status) {
                throw new Exception('Failed to send verification code');
            }

            return ShopittPlus::response(true, 'Otp has been resent', 200, (object)['expires_at' => $response->expires_at]);
        } catch (Exception $e) {
            Log::error('RESEND REGISTER OTP: Error Encountered: ' . $e->getMessage());

            return ShopittPlus::response(false, $e->getMessage(), 500);
        }
    }
}

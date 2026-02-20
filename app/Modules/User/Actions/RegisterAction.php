<?php

namespace App\Modules\User\Actions;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Api\V1\Otp\UserOtpController;
use App\Modules\User\Data\Auth\RegisterDTO;
use App\Modules\User\Models\User;
use App\Modules\User\Services\AuthTokenService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegisterAction
{
    public static function execute(RegisterDTO $dto)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'email' => $dto->email,
            ]);

            $tokens = resolve(AuthTokenService::class)->createTokensForUser($user);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return ShopittPlus::response(false, 'Registration failed: ' . $e->getMessage(), 500);
        }

        $emailSent = true;
        try {
            $otpController = resolve(UserOtpController::class);
            $response = $otpController->sendForVerification($dto->email, null);
            if (!$response->status) {
                $emailSent = false;
            }
        } catch (Exception $e) {
            $emailSent = false;
            Log::warning('Registration: OTP email failed (user can use Resend OTP)', [
                'email' => $dto->email,
                'error' => $e->getMessage(),
            ]);
        }

        $message = $emailSent
            ? 'Verify your email'
            : 'Verification code could not be sent. Please use Resend OTP to try again.';

        return ShopittPlus::response(true, $message, 201, [
            'token' => $tokens['token'],
            'refresh_token' => $tokens['refresh_token'],
            'email_sent' => $emailSent,
        ]);
    }
}
<?php

namespace App\Modules\User\Actions;

use App\Helpers\ShopittPlus;
use App\Modules\User\Data\Auth\LoginDTO;
use App\Modules\User\Models\User;
use App\Modules\User\Services\DeviceTokenService;
use App\Modules\User\Services\AuthTokenService;
use Illuminate\Support\Facades\Hash;

class LoginAction
{
    public static function execute(LoginDTO $dto)
    {
            $user = User::where('email', $dto->email)->first();
            $maxAttempts = 5;
            $lockoutMinutes = 15;
            if ($user) {
                // Check lockout
                if ($user->lockout_until && now()->lt($user->lockout_until)) {
                    return ShopittPlus::response(false, 'Account locked. Try again after ' . $user->lockout_until->diffForHumans(), 423);
                }
                // Check password
                if (!Hash::check($dto->password, $user->password)) {
                    $user->failed_login_attempts = ($user->failed_login_attempts ?? 0) + 1;
                    if ($user->failed_login_attempts >= $maxAttempts) {
                        $user->lockout_until = now()->addMinutes($lockoutMinutes);
                        $user->failed_login_attempts = 0;
                    }
                    $user->save();
                    return ShopittPlus::response(false, 'Invalid credentials', 401);
                }
                // Optional 2FA check
                if ($user->two_factor_enabled ?? false) {
                    if (empty($dto->otp_code)) {
                        return ShopittPlus::response(false, 'OTP code required for 2FA', 401);
                    }
                    try {
                        $otpService = app(\App\Modules\User\Services\OTPService::class);
                        $identifier = $otpService->getVerificationCodeIdentifier($dto->otp_code, null, $user->email);
                        $otpService->verifyOTP($dto->otp_code, $identifier, null, $user->email);
                    } catch (\InvalidArgumentException) {
                        return ShopittPlus::response(false, 'Invalid OTP code', 401);
                    }
                }
                // Reset failed attempts on success
                $user->failed_login_attempts = 0;
                $user->lockout_until = null;
                $user->save();
            } else {
                return ShopittPlus::response(false, 'Invalid credentials', 401);
            }

            $tokens = resolve(AuthTokenService::class)->createTokensForUser($user);

            if (!is_null($dto->fcm_device_token)) {
                resolve(DeviceTokenService::class)
                    ->saveDistinctTokenForUser($user, $dto->fcm_device_token);
            }

            $role = 'user';
            if ($user->driver) {
                $role = 'driver';
            } elseif ($user->vendor) {
                $role = 'vendor';
            }

            return ShopittPlus::response(true, 'Login successful', 200, [
                'token' => $tokens['token'],
                'refresh_token' => $tokens['refresh_token'],
                'role' => $role,
            ]);
    }
}

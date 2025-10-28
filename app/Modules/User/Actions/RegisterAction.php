<?php

namespace App\Modules\User\Actions;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Api\V1\Otp\UserOtpController;
use App\Modules\User\Data\Auth\RegisterDTO;
use App\Modules\User\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class RegisterAction
{
    public static function execute(RegisterDTO $dto)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'email' => $dto->email,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            $otpService = resolve(UserOtpController::class);
            $response = $otpService->sendForVerification(
                $dto->email,
                null,
            );

            if (!$response->status) {
                return ShopittPlus::response(false, 'Failed to send verification code', 400);
            }

            DB::commit();
            return ShopittPlus::response(true, 'User registered successfully', 201, ['token' => $token]);
        } catch (Exception $e) {
            DB::rollBack();
            return ShopittPlus::response(false, 'Registration failed: ' . $e->getMessage(), 500);
        }
    }
}
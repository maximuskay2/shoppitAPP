<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\DriverLoginRequest;
use App\Http\Requests\Api\V1\Driver\DriverRegisterRequest;
use App\Modules\User\Enums\UserStatusEnum;
use App\Modules\User\Models\Driver;
use App\Modules\User\Models\User;
use App\Modules\User\Services\DeviceTokenService;
use App\Http\Controllers\Api\V1\Otp\UserOtpController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(DriverRegisterRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $user = DB::transaction(function () use ($data) {
                if (User::where('email', $data['email'])->exists()) {
                    throw new \InvalidArgumentException('User with this email already exists.');
                }

                if (User::where('phone', $data['phone'])->exists()) {
                    throw new \InvalidArgumentException('User with this phone already exists.');
                }

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'password' => $data['password'],
                    'status' => UserStatusEnum::NEW,
                ]);

                Driver::create([
                    'user_id' => $user->id,
                    'vehicle_type' => $data['vehicle_type'],
                    'license_number' => $data['license_number'],
                    'is_verified' => false,
                    'is_online' => false,
                ]);

                return $user;
            });

            if (!empty($data['fcm_device_token'])) {
                resolve(DeviceTokenService::class)
                    ->saveDistinctTokenForUser($user, $data['fcm_device_token']);
            }

            $otpService = resolve(UserOtpController::class);
            $otpService->sendForVerification($user->email, null);

            $token = $user->createToken('auth_token')->plainTextToken;

            return ShopittPlus::response(true, 'Driver registered successfully. Verify your email to continue.', 201, [
                'token' => $token,
                'driver_id' => $user->driver?->id,
            ]);
        } catch (\InvalidArgumentException $e) {
            Log::error('DRIVER REGISTER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DRIVER REGISTER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to register driver', 500);
        }
    }

    public function login(DriverLoginRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $user = User::where('email', $data['email'])->first();
            if (!$user || !Hash::check($data['password'], $user->password)) {
                return ShopittPlus::response(false, 'Invalid credentials', 401);
            }

            if (!$user->driver) {
                return ShopittPlus::response(false, 'User is not registered as a driver.', 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            if (!empty($data['fcm_device_token'])) {
                resolve(DeviceTokenService::class)
                    ->saveDistinctTokenForUser($user, $data['fcm_device_token']);
            }

            return ShopittPlus::response(true, 'Login successful', 200, [
                'token' => $token,
                'role' => 'driver',
            ]);
        } catch (\Exception $e) {
            Log::error('DRIVER LOGIN: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to login driver', 500);
        }
    }
}

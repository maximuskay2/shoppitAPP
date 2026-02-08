<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\DriverChangePasswordRequest;
use App\Http\Requests\Api\V1\Driver\DriverProfileUpdateRequest;
use App\Http\Requests\Api\V1\Driver\DriverUpdateAvatarRequest;
use App\Modules\User\Models\User;
use App\Modules\User\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinaryService) {}

    public function show(): JsonResponse
    {
        try {
            $user = User::with('driver.vehicles')->find(Auth::id());

            if (!$user || !$user->driver) {
                return ShopittPlus::response(false, 'Driver profile not found.', 404);
            }

            return ShopittPlus::response(true, 'Driver profile retrieved successfully', 200, [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                ],
                'driver' => [
                    'vehicle_type' => $user->driver->vehicle_type,
                    'license_number' => $user->driver->license_number,
                    'is_verified' => $user->driver->is_verified,
                    'is_online' => $user->driver->is_online,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('DRIVER PROFILE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve driver profile', 500);
        }
    }

    public function update(DriverProfileUpdateRequest $request): JsonResponse
    {
        try {
            $user = User::with('driver')->find(Auth::id());

            if (!$user || !$user->driver) {
                return ShopittPlus::response(false, 'Driver profile not found.', 404);
            }

            $data = $request->validated();

            $user->update([
                'name' => $data['name'] ?? $user->name,
                'phone' => $data['phone'] ?? $user->phone,
            ]);

            $user->driver->update([
                'vehicle_type' => $data['vehicle_type'] ?? $user->driver->vehicle_type,
                'license_number' => $data['license_number'] ?? $user->driver->license_number,
            ]);

            if (isset($data['vehicle_type']) || isset($data['license_number'])) {
                $activeVehicle = $user->driver->vehicles()->where('is_active', true)->first();
                if ($activeVehicle) {
                    $activeVehicle->update([
                        'vehicle_type' => $data['vehicle_type'] ?? $activeVehicle->vehicle_type,
                        'license_number' => $data['license_number'] ?? $activeVehicle->license_number,
                    ]);
                }
            }

            return ShopittPlus::response(true, 'Driver profile updated successfully', 200, [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                ],
                'driver' => [
                    'vehicle_type' => $user->driver->vehicle_type,
                    'license_number' => $user->driver->license_number,
                    'is_verified' => $user->driver->is_verified,
                    'is_online' => $user->driver->is_online,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('DRIVER UPDATE PROFILE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update driver profile', 500);
        }
    }

    public function updateAvatar(DriverUpdateAvatarRequest $request): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());

            if (!$driver) {
                return ShopittPlus::response(false, 'Driver profile not found.', 404);
            }

            $upload = $this->cloudinaryService->uploadUserAvatar(
                $request->file('avatar'),
                $driver->id
            );

            if (!$upload['success']) {
                return ShopittPlus::response(false, $upload['message'] ?? 'Upload failed', 500);
            }

            $driver->update([
                'avatar' => $upload['data']['secure_url'] ?? $upload['data']['url'] ?? '',
            ]);

            return ShopittPlus::response(true, 'Driver avatar updated successfully', 200, [
                'avatar' => $driver->avatar,
            ]);
        } catch (\Exception $e) {
            Log::error('DRIVER UPDATE AVATAR: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update avatar', 500);
        }
    }

    public function changePassword(DriverChangePasswordRequest $request): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());

            if (!$driver) {
                return ShopittPlus::response(false, 'Driver profile not found.', 404);
            }

            if (!Hash::check($request->input('current_password'), $driver->password)) {
                return ShopittPlus::response(false, 'Current password is incorrect.', 422, [
                    'current_password' => ['Current password is incorrect.'],
                ]);
            }

            $driver->update([
                'password' => $request->input('password'),
            ]);

            return ShopittPlus::response(true, 'Password updated successfully', 200);
        } catch (\Exception $e) {
            Log::error('DRIVER CHANGE PASSWORD: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update password', 500);
        }
    }
}

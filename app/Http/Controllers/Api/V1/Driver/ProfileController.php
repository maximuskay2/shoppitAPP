<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\DriverProfileUpdateRequest;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        try {
            $user = User::with('driver')->find(Auth::id());

            if (!$user || !$user->driver) {
                return ShopittPlus::response(false, 'Driver profile not found.', 404);
            }

            return ShopittPlus::response(true, 'Driver profile retrieved successfully', 200, [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
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

            return ShopittPlus::response(true, 'Driver profile updated successfully', 200, [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
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
}

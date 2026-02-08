<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\DriverVehicleStoreRequest;
use App\Http\Requests\Api\V1\Driver\DriverVehicleUpdateRequest;
use App\Modules\User\Models\DriverVehicle;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DriverVehicleController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $user = User::with('driver.vehicles')->find(Auth::id());

            if (!$user || !$user->driver) {
                return ShopittPlus::response(false, 'Driver profile not found.', 404);
            }

            $vehicles = $user->driver->vehicles
                ->sortByDesc('is_active')
                ->values()
                ->map(fn (DriverVehicle $vehicle) => $this->formatVehicle($vehicle));

            return ShopittPlus::response(true, 'Driver vehicles retrieved successfully', 200, [
                'vehicles' => $vehicles,
            ]);
        } catch (\Exception $e) {
            Log::error('DRIVER VEHICLES: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve vehicles', 500);
        }
    }

    public function store(DriverVehicleStoreRequest $request): JsonResponse
    {
        try {
            $user = User::with('driver.vehicles')->find(Auth::id());

            if (!$user || !$user->driver) {
                return ShopittPlus::response(false, 'Driver profile not found.', 404);
            }

            $data = $request->validated();
            $vehicles = $user->driver->vehicles;
            $makeActive = (bool) ($data['is_active'] ?? false) || $vehicles->isEmpty();

            if ($makeActive) {
                $user->driver->vehicles()->update(['is_active' => false]);
            }

            $vehicle = $user->driver->vehicles()->create([
                'vehicle_type' => $data['vehicle_type'],
                'license_number' => $data['license_number'] ?? null,
                'plate_number' => $data['plate_number'] ?? null,
                'color' => $data['color'] ?? null,
                'model' => $data['model'] ?? null,
                'is_active' => $makeActive,
            ]);

            if ($makeActive) {
                $this->syncDriverProfile($user, $vehicle);
            }

            return ShopittPlus::response(true, 'Vehicle added successfully', 201, [
                'vehicle' => $this->formatVehicle($vehicle),
            ]);
        } catch (\Exception $e) {
            Log::error('DRIVER VEHICLE STORE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to add vehicle', 500);
        }
    }

    public function update(DriverVehicleUpdateRequest $request, string $id): JsonResponse
    {
        try {
            $user = User::with('driver.vehicles')->find(Auth::id());

            if (!$user || !$user->driver) {
                return ShopittPlus::response(false, 'Driver profile not found.', 404);
            }

            $vehicle = $user->driver->vehicles()->where('id', $id)->first();
            if (!$vehicle) {
                return ShopittPlus::response(false, 'Vehicle not found.', 404);
            }

            $data = $request->validated();
            $makeActive = (bool) ($data['is_active'] ?? false);

            $vehicle->update([
                'vehicle_type' => $data['vehicle_type'] ?? $vehicle->vehicle_type,
                'license_number' => $data['license_number'] ?? $vehicle->license_number,
                'plate_number' => $data['plate_number'] ?? $vehicle->plate_number,
                'color' => $data['color'] ?? $vehicle->color,
                'model' => $data['model'] ?? $vehicle->model,
            ]);

            if ($makeActive) {
                $user->driver->vehicles()->where('id', '!=', $vehicle->id)->update([
                    'is_active' => false,
                ]);
                $vehicle->update(['is_active' => true]);
                $this->syncDriverProfile($user, $vehicle);
            }

            return ShopittPlus::response(true, 'Vehicle updated successfully', 200, [
                'vehicle' => $this->formatVehicle($vehicle->fresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('DRIVER VEHICLE UPDATE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update vehicle', 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = User::with('driver.vehicles')->find(Auth::id());

            if (!$user || !$user->driver) {
                return ShopittPlus::response(false, 'Driver profile not found.', 404);
            }

            $vehicle = $user->driver->vehicles()->where('id', $id)->first();
            if (!$vehicle) {
                return ShopittPlus::response(false, 'Vehicle not found.', 404);
            }

            $wasActive = (bool) $vehicle->is_active;
            $vehicle->delete();

            if ($wasActive) {
                $nextVehicle = $user->driver->vehicles()->first();
                if ($nextVehicle) {
                    $nextVehicle->update(['is_active' => true]);
                    $this->syncDriverProfile($user, $nextVehicle);
                }
            }

            return ShopittPlus::response(true, 'Vehicle deleted successfully', 200);
        } catch (\Exception $e) {
            Log::error('DRIVER VEHICLE DELETE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete vehicle', 500);
        }
    }

    private function formatVehicle(DriverVehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'vehicle_type' => $vehicle->vehicle_type,
            'license_number' => $vehicle->license_number,
            'plate_number' => $vehicle->plate_number,
            'color' => $vehicle->color,
            'model' => $vehicle->model,
            'is_active' => (bool) $vehicle->is_active,
            'created_at' => $vehicle->created_at,
            'updated_at' => $vehicle->updated_at,
        ];
    }

    private function syncDriverProfile(User $user, DriverVehicle $vehicle): void
    {
        if (!$user->driver) {
            return;
        }

        $user->driver->update([
            'vehicle_type' => $vehicle->vehicle_type,
            'license_number' => $vehicle->license_number,
        ]);
    }
}

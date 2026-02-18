<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvailabilityController extends Controller
{
    /**
     * Get driver availability schedule
     */
    public function show(): JsonResponse
    {
        $driver = User::with('driver')->find(Auth::id());
        if (!$driver || !$driver->driver) {
            return ShopittPlus::response(false, 'Driver profile not found.', 404);
        }
        // Example: return static schedule (replace with real DB logic)
        $schedule = $driver->driver->availability_schedule ?? [
            'monday' => ['08:00', '18:00'],
            'tuesday' => ['08:00', '18:00'],
            'wednesday' => ['08:00', '18:00'],
            'thursday' => ['08:00', '18:00'],
            'friday' => ['08:00', '18:00'],
            'saturday' => ['08:00', '14:00'],
            'sunday' => [],
        ];
        return ShopittPlus::response(true, 'Driver availability schedule', 200, ['schedule' => $schedule]);
    }

    /**
     * Update driver availability schedule
     */
    public function update(Request $request): JsonResponse
    {
        $driver = User::with('driver')->find(Auth::id());
        if (!$driver || !$driver->driver) {
            return ShopittPlus::response(false, 'Driver profile not found.', 404);
        }
        $data = $request->validate([
            'schedule' => 'required|array',
        ]);
        // Example: save to driver profile (replace with real DB logic)
        $driver->driver->availability_schedule = $data['schedule'];
        $driver->driver->save();
        return ShopittPlus::response(true, 'Availability schedule updated', 200, ['schedule' => $data['schedule']]);
    }
}

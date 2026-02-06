<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\DriverStatusUpdateRequest;
use App\Modules\Commerce\Events\DriverStatusUpdated;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StatusController extends Controller
{
    public function update(DriverStatusUpdateRequest $request): JsonResponse
    {
        try {
            $user = User::with('driver')->find(Auth::id());

            if (!$user || !$user->driver) {
                return ShopittPlus::response(false, 'Driver profile not found.', 404);
            }

            $user->driver->update([
                'is_online' => $request->boolean('is_online'),
            ]);

            event(new DriverStatusUpdated($user));

            return ShopittPlus::response(true, 'Driver status updated successfully', 200, [
                'is_online' => $user->driver->is_online,
            ]);
        } catch (\Exception $e) {
            Log::error('DRIVER STATUS UPDATE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update driver status', 500);
        }
    }
}

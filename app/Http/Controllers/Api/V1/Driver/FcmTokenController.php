<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\DriverFcmTokenRequest;
use App\Modules\User\Services\DeviceTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FcmTokenController extends Controller
{
    public function store(DriverFcmTokenRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ShopittPlus::response(false, 'Unauthenticated.', 401);
            }

            resolve(DeviceTokenService::class)
                ->saveDistinctTokenForUser($user, $request->validated()['fcm_device_token']);

            return ShopittPlus::response(true, 'FCM token registered successfully', 200);
        } catch (\Exception $e) {
            Log::error('DRIVER FCM TOKEN: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to register FCM token', 500);
        }
    }
}

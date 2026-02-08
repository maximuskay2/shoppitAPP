<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationPreferencesController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return ShopittPlus::response(true, 'Notification preferences retrieved successfully', 200, [
            'push_in_app_notifications' => (bool) $user->push_in_app_notifications,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'push_in_app_notifications' => ['required', 'boolean'],
            ]);

            $user = $request->user();
            $user->update([
                'push_in_app_notifications' => $data['push_in_app_notifications'],
            ]);

            return ShopittPlus::response(true, 'Notification preferences updated successfully', 200, [
                'push_in_app_notifications' => (bool) $user->push_in_app_notifications,
            ]);
        } catch (\Exception $e) {
            Log::error('UPDATE NOTIFICATION PREFS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update preferences', 500);
        }
    }
}

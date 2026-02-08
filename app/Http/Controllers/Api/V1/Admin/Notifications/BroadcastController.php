<?php

namespace App\Http\Controllers\Api\V1\Admin\Notifications;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BroadcastController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'title' => ['required', 'string', 'max:120'],
                'message' => ['required', 'string', 'max:1000'],
                'audience' => ['nullable', 'string'],
            ]);

            Log::info('ADMIN BROADCAST NOTIFICATION', [
                'title' => $data['title'],
                'audience' => $data['audience'] ?? 'all',
            ]);

            // Send notification immediately to all users (or filtered audience)
            $audience = $data['audience'] ?? 'all';
            $users = \App\Modules\User\Models\User::query();
            if ($audience !== 'all') {
                $users->where('role', $audience);
            }
            $users = $users->get();
            \Illuminate\Support\Facades\Notification::send($users, new \App\Modules\User\Notifications\AdminBroadcastNotification($data['title'], $data['message']));

            return ShopittPlus::response(true, 'Broadcast sent', 200, [
                'sent' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('ADMIN BROADCAST: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to send broadcast', 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Messaging\Models\Conversation;
use App\Modules\Messaging\Services\MessagingService;
use App\Modules\User\Models\Admin;
use App\Modules\User\Models\DriverSupportTicket;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SosController extends Controller
{
    public function __construct(private readonly MessagingService $messagingService) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
            'order_id' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        try {
            $driver = User::find(Auth::id());
            $sosMessage = $data['reason'] ?? 'Driver triggered an SOS alert.';

            $ticket = DriverSupportTicket::create([
                'driver_id' => $driver->id,
                'subject' => 'SOS Emergency',
                'message' => $sosMessage,
                'priority' => 'HIGH',
                'status' => 'OPEN',
                'meta' => [
                    'order_id' => $data['order_id'] ?? null,
                    'location' => [
                        'latitude' => $data['latitude'] ?? null,
                        'longitude' => $data['longitude'] ?? null,
                    ],
                    'triggered_at' => now()->toISOString(),
                ],
            ]);

            // Link SOS to messaging: create/get admin-driver conversation and post SOS as message
            $admin = Admin::where('is_super_admin', true)->first() ?? Admin::first();
            if ($admin) {
                $conversation = $this->messagingService->getOrCreateConversation(
                    Conversation::TYPE_ADMIN_DRIVER,
                    $driver,
                    $admin,
                    'admin',
                    null
                );
                $this->messagingService->sendMessage($conversation, $driver, "🆘 SOS: {$sosMessage}");
                $meta = $ticket->meta ?? [];
                $meta['conversation_id'] = $conversation->id;
                $ticket->update(['meta' => $meta]);
            }

            Log::warning('DRIVER SOS ALERT', [
                'driver_id' => $driver->id,
                'ticket_id' => $ticket->id,
                'order_id' => $data['order_id'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);

            return ShopittPlus::response(true, 'SOS alert sent successfully', 201, $ticket);
        } catch (\Exception $e) {
            Log::error('DRIVER SOS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to send SOS alert', 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Messaging\Models\Conversation;
use App\Modules\Messaging\Services\MessagingService;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class MessagingController extends Controller
{
    public function __construct(private readonly MessagingService $messagingService) {}

    /**
     * List conversations for the authenticated driver.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            /** @var User $driver */
            $driver = Auth::user();
            if (!$driver->driver) {
                return ShopittPlus::response(false, 'Driver profile not found', 404);
            }

            $orderId = $request->query('order_id');
            $conversations = $this->messagingService->listConversationsFor($driver, $orderId);

            $data = $conversations->map(fn ($c) => $this->mapConversation($c, $driver));

            return ShopittPlus::response(true, 'Conversations retrieved', 200, $data);
        } catch (\Exception $e) {
            Log::error('DRIVER MESSAGING: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to list conversations', 500);
        }
    }

    /**
     * Get or create a conversation with customer or vendor for an active order.
     * Body: type (driver_customer|driver_vendor), order_id (required), other_id (user id - customer or vendor user)
     */
    public function getOrCreate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:driver_customer,driver_vendor'],
            'order_id' => ['required', 'uuid', 'exists:orders,id'],
            'other_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        try {
            /** @var User $driver */
            $driver = Auth::user();
            if (!$driver->driver) {
                return ShopittPlus::response(false, 'Driver profile not found', 404);
            }

            $other = User::findOrFail($data['other_id']);
            $otherRole = $data['type'] === 'driver_customer' ? 'customer' : 'vendor';

            $conversation = $this->messagingService->getOrCreateConversation(
                $data['type'],
                $driver,
                $other,
                $otherRole,
                $data['order_id']
            );

            return ShopittPlus::response(true, 'Conversation ready', 200, $this->mapConversation($conversation, $driver));
        } catch (InvalidArgumentException $e) {
            return ShopittPlus::response(false, $e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('DRIVER MESSAGING CREATE: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create conversation', 500);
        }
    }

    /**
     * Get or create admin-driver conversation. Driver can initiate to message admin.
     */
    public function getOrCreateWithAdmin(Request $request): JsonResponse
    {
        try {
            /** @var User $driver */
            $driver = Auth::user();
            if (!$driver->driver) {
                return ShopittPlus::response(false, 'Driver profile not found', 404);
            }

            $admin = \App\Modules\User\Models\Admin::where('is_super_admin', true)->first()
                ?? \App\Modules\User\Models\Admin::first();

            if (!$admin) {
                return ShopittPlus::response(false, 'No admin available for messaging', 503);
            }

            $conversation = $this->messagingService->getOrCreateConversation(
                Conversation::TYPE_ADMIN_DRIVER,
                $driver,
                $admin,
                'admin',
                null
            );

            return ShopittPlus::response(true, 'Conversation ready', 200, $this->mapConversation($conversation, $driver));
        } catch (InvalidArgumentException $e) {
            return ShopittPlus::response(false, $e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('DRIVER MESSAGING ADMIN: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create conversation', 500);
        }
    }

    /**
     * Get messages in a conversation.
     */
    public function messages(string $conversationId, Request $request): JsonResponse
    {
        try {
            $driver = Auth::user();
            $conversation = Conversation::findOrFail($conversationId);

            $paginator = $this->messagingService->getMessages(
                $conversation,
                $driver,
                (int) $request->query('per_page', 50)
            );

            $this->messagingService->markAsRead($conversation, $driver);

            $items = $paginator->getCollection()->map(fn ($m) => $this->mapMessage($m));

            return ShopittPlus::response(true, 'Messages retrieved', 200, [
                'data' => $items->reverse()->values()->all(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            return ShopittPlus::response(false, $e->getMessage(), 403);
        } catch (\Exception $e) {
            Log::error('DRIVER MESSAGING MESSAGES: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to get messages', 500);
        }
    }

    /**
     * Send a message.
     */
    public function send(Request $request, string $conversationId): JsonResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $driver = Auth::user();
            $conversation = Conversation::findOrFail($conversationId);

            $message = $this->messagingService->sendMessage($conversation, $driver, $data['content']);

            return ShopittPlus::response(true, 'Message sent', 201, $this->mapMessage($message));
        } catch (InvalidArgumentException $e) {
            return ShopittPlus::response(false, $e->getMessage(), 403);
        } catch (\Exception $e) {
            Log::error('DRIVER MESSAGING SEND: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to send message', 500);
        }
    }

    private function mapConversation(Conversation $conversation, User $driver): array
    {
        $otherParticipant = $conversation->participants()
            ->where(function ($q) use ($driver) {
                $q->where('participant_type', '!=', 'user')
                    ->orWhere('participant_id', '!=', $driver->id);
            })
            ->first();

        $otherUser = null;
        $otherAdmin = null;
        if ($otherParticipant) {
            if ($otherParticipant->participant_type === 'admin') {
                $otherAdmin = \App\Modules\User\Models\Admin::find($otherParticipant->participant_id);
            } else {
                $otherUser = User::find($otherParticipant->participant_id);
            }
        }

        $latest = $conversation->messages()->latest()->first();

        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'order_id' => $conversation->order_id,
            'other' => $otherUser ? [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'email' => $otherUser->email,
                'phone' => $otherUser->phone,
            ] : ($otherAdmin ? [
                'id' => $otherAdmin->id,
                'name' => $otherAdmin->name,
                'email' => $otherAdmin->email,
            ] : null),
            'latest_message' => $latest ? $this->mapMessage($latest) : null,
            'updated_at' => $conversation->updated_at?->toIso8601String(),
        ];
    }

    private function mapMessage($message): array
    {
        $sender = $message->sender_type === 'admin'
            ? \App\Modules\User\Models\Admin::find($message->sender_id)
            : User::find($message->sender_id);

        return [
            'id' => $message->id,
            'content' => $message->content,
            'sender_type' => $message->sender_type,
            'sender_id' => $message->sender_id,
            'sender_name' => $sender?->name ?? 'Unknown',
            'is_mine' => $message->sender_type === 'user' && $message->sender_id === Auth::id(),
            'read_at' => $message->read_at?->toIso8601String(),
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }
}

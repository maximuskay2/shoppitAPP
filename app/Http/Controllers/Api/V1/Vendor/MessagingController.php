<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Commerce\Models\Order;
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
     * List conversations for the authenticated vendor.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            if (!$user->vendor) {
                return ShopittPlus::response(false, 'Vendor profile not found', 404);
            }

            $orderId = $request->query('order_id');
            $conversations = $this->messagingService->listConversationsFor($user, $orderId);

            $data = $conversations->map(fn ($c) => $this->mapConversation($c, $user));

            return ShopittPlus::response(true, 'Conversations retrieved', 200, $data);
        } catch (\Exception $e) {
            Log::error('VENDOR MESSAGING: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to list conversations', 500);
        }
    }

    /**
     * Get or create driver-vendor conversation for an order.
     */
    public function getOrCreateWithDriver(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'uuid', 'exists:orders,id'],
        ]);

        try {
            /** @var User $user */
            $user = Auth::user();
            if (!$user->vendor) {
                return ShopittPlus::response(false, 'Vendor profile not found', 404);
            }

            $order = Order::with(['driver', 'vendor'])->findOrFail($data['order_id']);

            if ($order->vendor_id !== $user->vendor->id) {
                return ShopittPlus::response(false, 'Order does not belong to your store', 403);
            }

            $driver = $order->driver;
            if (!$driver) {
                return ShopittPlus::response(false, 'Order has no assigned driver yet', 422);
            }

            $conversation = $this->messagingService->getOrCreateConversation(
                Conversation::TYPE_DRIVER_VENDOR,
                $user,
                $driver,
                'driver',
                $data['order_id']
            );

            return ShopittPlus::response(true, 'Conversation ready', 200, $this->mapConversation($conversation, $user));
        } catch (InvalidArgumentException $e) {
            return ShopittPlus::response(false, $e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('VENDOR MESSAGING CREATE: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create conversation', 500);
        }
    }

    /**
     * Get messages in a conversation.
     */
    public function messages(string $conversationId, Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $conversation = Conversation::findOrFail($conversationId);

            $paginator = $this->messagingService->getMessages(
                $conversation,
                $user,
                (int) $request->query('per_page', 50)
            );

            $this->messagingService->markAsRead($conversation, $user);

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
            Log::error('VENDOR MESSAGING MESSAGES: ' . $e->getMessage());
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
            $user = Auth::user();
            $conversation = Conversation::findOrFail($conversationId);

            $message = $this->messagingService->sendMessage($conversation, $user, $data['content']);

            return ShopittPlus::response(true, 'Message sent', 201, $this->mapMessage($message));
        } catch (InvalidArgumentException $e) {
            return ShopittPlus::response(false, $e->getMessage(), 403);
        } catch (\Exception $e) {
            Log::error('VENDOR MESSAGING SEND: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to send message', 500);
        }
    }

    private function mapConversation(Conversation $conversation, User $user): array
    {
        $other = $conversation->participants()
            ->where(function ($q) use ($user) {
                $q->where('participant_type', '!=', 'user')
                    ->orWhere('participant_id', '!=', $user->id);
            })
            ->first();

        $otherUser = $other && $other->participant_type === 'user'
            ? User::find($other->participant_id)
            : null;
        $otherAdmin = $other && $other->participant_type === 'admin'
            ? \App\Modules\User\Models\Admin::find($other->participant_id)
            : null;

        $latest = $conversation->messages()->latest()->first();

        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'order_id' => $conversation->order_id,
            'other' => $otherUser ? [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'email' => $otherUser->email,
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

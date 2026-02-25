<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Messaging\Models\Conversation;
use App\Modules\Messaging\Services\MessagingService;
use App\Modules\User\Models\Admin;
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
     * List conversations for the authenticated admin.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            /** @var Admin $admin */
            $admin = Auth::guard('admin-api')->user();
            $orderId = $request->query('order_id');

            $conversations = $this->messagingService->listConversationsFor($admin, $orderId);

            $data = $conversations->map(fn ($c) => $this->mapConversation($c, $admin));

            return ShopittPlus::response(true, 'Conversations retrieved', 200, $data);
        } catch (\Exception $e) {
            Log::error('ADMIN MESSAGING: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to list conversations', 500);
        }
    }

    /**
     * Get or create a conversation with a driver, customer, or vendor.
     * Body: type (admin_driver|admin_customer|admin_vendor), other_id (user id), order_id (optional)
     */
    public function getOrCreate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:admin_driver,admin_customer,admin_vendor'],
            'other_id' => ['required', 'uuid', 'exists:users,id'],
            'order_id' => ['nullable', 'uuid', 'exists:orders,id'],
        ]);

        try {
            /** @var Admin $admin */
            $admin = Auth::guard('admin-api')->user();
            $other = User::findOrFail($data['other_id']);

            $otherRole = match ($data['type']) {
                'admin_driver' => 'driver',
                'admin_customer' => 'customer',
                'admin_vendor' => 'vendor',
                default => null,
            };

            $conversation = $this->messagingService->getOrCreateConversation(
                $data['type'],
                $admin,
                $other,
                $otherRole,
                $data['order_id'] ?? null
            );

            return ShopittPlus::response(true, 'Conversation ready', 200, $this->mapConversation($conversation, $admin));
        } catch (InvalidArgumentException $e) {
            return ShopittPlus::response(false, $e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('ADMIN MESSAGING CREATE: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create conversation', 500);
        }
    }

    /**
     * Get messages in a conversation.
     */
    public function messages(string $conversationId, Request $request): JsonResponse
    {
        try {
            $admin = Auth::guard('admin-api')->user();
            $conversation = Conversation::findOrFail($conversationId);

            $paginator = $this->messagingService->getMessages(
                $conversation,
                $admin,
                (int) $request->query('per_page', 50)
            );

            $this->messagingService->markAsRead($conversation, $admin);

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
            Log::error('ADMIN MESSAGING MESSAGES: ' . $e->getMessage());
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
            $admin = Auth::guard('admin-api')->user();
            $conversation = Conversation::findOrFail($conversationId);

            $message = $this->messagingService->sendMessage($conversation, $admin, $data['content']);

            return ShopittPlus::response(true, 'Message sent', 201, $this->mapMessage($message));
        } catch (InvalidArgumentException $e) {
            return ShopittPlus::response(false, $e->getMessage(), 403);
        } catch (\Exception $e) {
            Log::error('ADMIN MESSAGING SEND: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to send message', 500);
        }
    }

    private function mapConversation(Conversation $conversation, Admin $admin): array
    {
        $other = $conversation->participants()
            ->where('participant_type', 'user')
            ->first();
        $otherUser = $other ? User::find($other->participant_id) : null;

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
            ] : null,
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
            'read_at' => $message->read_at?->toIso8601String(),
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }
}

<?php

namespace App\Modules\Messaging\Services;

use App\Modules\Commerce\Models\Order;
use App\Modules\Messaging\Models\Conversation;
use App\Modules\Messaging\Models\ConversationParticipant;
use App\Modules\Messaging\Models\Message;
use App\Modules\User\Models\Admin;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MessagingService
{
    /**
     * Allowed conversation types and who can initiate them.
     * NO customer-vendor direct communication.
     */
    private const ALLOWED_TYPES = [
        Conversation::TYPE_ADMIN_DRIVER,
        Conversation::TYPE_ADMIN_CUSTOMER,
        Conversation::TYPE_ADMIN_VENDOR,
        Conversation::TYPE_DRIVER_CUSTOMER,
        Conversation::TYPE_DRIVER_VENDOR,
    ];

    /**
     * Get or create a conversation between participants.
     *
     * @param string $type Conversation type
     * @param Admin|User $sender The one initiating
     * @param User $other The other participant (user - customer, vendor user, or driver user)
     * @param string|null $otherRole Role of other: driver, customer, vendor
     * @param string|null $orderId For driver_customer and driver_vendor, the active order
     */
    public function getOrCreateConversation(
        string $type,
        Admin|User $sender,
        Admin|User $other,
        ?string $otherRole = null,
        ?string $orderId = null
    ): Conversation {
        $this->validateConversationType($type, $sender, $other, $otherRole, $orderId);

        return DB::transaction(function () use ($type, $sender, $other, $otherRole, $orderId) {
            $existing = $this->findExistingConversation($type, $sender, $other, $orderId);
            if ($existing) {
                return $existing;
            }

            $conversation = Conversation::create([
                'type' => $type,
                'order_id' => $orderId,
            ]);

            $this->addParticipant($conversation, $sender);
            $this->addParticipant($conversation, $other, $otherRole);

            return $conversation;
        });
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(Conversation $conversation, Admin|User $sender, string $content): Message
    {
        if (!$this->canSendInConversation($conversation, $sender)) {
            throw new InvalidArgumentException('You are not allowed to send messages in this conversation.');
        }

        $senderType = $sender instanceof Admin ? 'admin' : 'user';
        $senderId = $sender->id;

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'content' => $content,
        ]);

        $conversation->touch();

        return $message;
    }

    /**
     * List conversations for the given actor (admin, driver, customer, vendor).
     */
    public function listConversationsFor(Admin|User $actor, ?string $orderId = null): \Illuminate\Database\Eloquent\Collection
    {
        $participantType = $actor instanceof Admin ? 'admin' : 'user';
        $participantId = $actor->id;

        $query = Conversation::query()
            ->whereHas('participants', function ($q) use ($participantType, $participantId) {
                $q->where('participant_type', $participantType)
                    ->where('participant_id', $participantId);
            })
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->with('order')
            ->orderByDesc('updated_at');

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        return $query->get();
    }

    /**
     * Get messages for a conversation (paginated).
     */
    public function getMessages(Conversation $conversation, Admin|User $actor, int $perPage = 50)
    {
        if (!$this->canAccessConversation($conversation, $actor)) {
            throw new InvalidArgumentException('You do not have access to this conversation.');
        }

        return $conversation->messages()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Mark messages as read.
     */
    public function markAsRead(Conversation $conversation, Admin|User $actor): void
    {
        if (!$this->canAccessConversation($conversation, $actor)) {
            return;
        }

        $actorType = $actor instanceof Admin ? 'admin' : 'user';
        $actorId = $actor->id;
        $conversation->messages()
            ->where(function ($q) use ($actorType, $actorId) {
                $q->where('sender_type', '!=', $actorType)
                    ->orWhere('sender_id', '!=', $actorId);
            })
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function validateConversationType(
        string $type,
        Admin|User $sender,
        Admin|User $other,
        ?string $otherRole,
        ?string $orderId
    ): void {
        if (!in_array($type, self::ALLOWED_TYPES)) {
            throw new InvalidArgumentException("Invalid conversation type: {$type}");
        }

        // Customer-Vendor is NOT allowed
        if ($type === 'customer_vendor' || $type === 'vendor_customer') {
            throw new InvalidArgumentException('Direct communication between customer and vendor is not allowed.');
        }

        switch ($type) {
            case Conversation::TYPE_ADMIN_DRIVER:
                if ($sender instanceof Admin) {
                    if (!($other instanceof User) || !$other->driver) {
                        throw new InvalidArgumentException('Admin-Driver conversation requires driver (user) as other.');
                    }
                } else {
                    if (!($other instanceof Admin)) {
                        throw new InvalidArgumentException('Admin-Driver conversation requires admin as other when driver initiates.');
                    }
                }
                break;
            case Conversation::TYPE_ADMIN_CUSTOMER:
                if (!($sender instanceof Admin)) {
                    throw new InvalidArgumentException('Admin-Customer conversation requires admin sender.');
                }
                break;
            case Conversation::TYPE_ADMIN_VENDOR:
                if (!($sender instanceof Admin) || !($other instanceof User) || !$other->vendor) {
                    throw new InvalidArgumentException('Admin-Vendor conversation requires admin sender and vendor user as other.');
                }
                break;
            case Conversation::TYPE_DRIVER_CUSTOMER:
                if (!($sender instanceof User) || !($other instanceof User) || !$orderId) {
                    throw new InvalidArgumentException('Driver-Customer conversation requires user participants and active order.');
                }
                $isDriverSender = $sender->driver !== null;
                $isDriverOther = $other->driver !== null;
                if (!$isDriverSender && !$isDriverOther) {
                    throw new InvalidArgumentException('One participant must be the driver.');
                }
                if ($isDriverSender && $isDriverOther) {
                    throw new InvalidArgumentException('One participant must be the customer.');
                }
                $driverId = $isDriverSender ? $sender->id : $other->id;
                $customerId = $isDriverSender ? $other->id : $sender->id;
                $order = Order::find($orderId);
                if (!$order || $order->driver_id !== $driverId || $order->user_id !== $customerId) {
                    throw new InvalidArgumentException('Order must involve this driver and customer.');
                }
                break;
            case Conversation::TYPE_DRIVER_VENDOR:
                if (!($sender instanceof User) || !($other instanceof User) || !$orderId) {
                    throw new InvalidArgumentException('Driver-Vendor conversation requires user participants and active order.');
                }
                $isDriverSender = $sender->driver !== null;
                $isVendorOther = $other->vendor !== null;
                $isVendorSender = $sender->vendor !== null;
                $isDriverOther = $other->driver !== null;
                if (!($isDriverSender && $isVendorOther) && !($isVendorSender && $isDriverOther)) {
                    throw new InvalidArgumentException('One participant must be driver and one must be vendor.');
                }
                $driverId = $isDriverSender ? $sender->id : $other->id;
                $vendorUserId = $isDriverSender ? $other->id : $sender->id;
                $order = Order::find($orderId);
                $orderVendorUserId = $order?->vendor?->user_id;
                if (!$order || $order->driver_id !== $driverId || $orderVendorUserId !== $vendorUserId) {
                    throw new InvalidArgumentException('Order must involve this driver and vendor.');
                }
                break;
        }
    }

    private function findExistingConversation(
        string $type,
        Admin|User $sender,
        Admin|User $other,
        ?string $orderId
    ): ?Conversation {
        $senderType = $sender instanceof Admin ? 'admin' : 'user';
        $senderId = $sender->id;
        $otherType = $other instanceof Admin ? 'admin' : 'user';
        $otherId = $other->id;

        $query = Conversation::where('type', $type)
            ->whereHas('participants', fn ($q) => $q->where('participant_type', $senderType)->where('participant_id', $senderId))
            ->whereHas('participants', fn ($q) => $q->where('participant_type', $otherType)->where('participant_id', $otherId));

        if ($orderId) {
            $query->where('order_id', $orderId);
        } else {
            $query->whereNull('order_id');
        }

        return $query->first();
    }

    private function addParticipant(Conversation $conversation, Admin|User $participant, ?string $role = null): void
    {
        $type = $participant instanceof Admin ? 'admin' : 'user';
        $role = $role ?? ($type === 'admin' ? 'admin' : $this->inferRole($participant));

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'participant_type' => $type,
            'participant_id' => $participant->id,
            'role' => $role,
        ]);
    }

    private function inferRole(User $user): string
    {
        if ($user->driver) return 'driver';
        if ($user->vendor) return 'vendor';
        return 'customer';
    }

    private function canSendInConversation(Conversation $conversation, Admin|User $sender): bool
    {
        return $conversation->participants()
            ->where('participant_type', $sender instanceof Admin ? 'admin' : 'user')
            ->where('participant_id', $sender->id)
            ->exists();
    }

    private function canAccessConversation(Conversation $conversation, Admin|User $actor): bool
    {
        return $conversation->participants()
            ->where('participant_type', $actor instanceof Admin ? 'admin' : 'user')
            ->where('participant_id', $actor->id)
            ->exists();
    }
}

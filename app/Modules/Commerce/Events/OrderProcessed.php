<?php

namespace App\Modules\Commerce\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $cartId,
        public string $cartVendorId,
        public string $userId,
        public string $vendorId,
        public float $grossTotal,
        public float $couponDiscount,
        public float $netTotal,
        public float $deliveryFee,
        public string $currency,
        public ?string $couponId,
        public ?string $couponCode,
        public string $paymentReference,
        public ?string $processorTransactionId,
        public ?string $receiverDeliveryAddress,
        public ?float $deliveryLatitude,
        public ?float $deliveryLongitude,
        public ?string $receiverName,
        public ?string $receiverEmail,
        public ?string $receiverPhone,
        public ?string $orderNotes,
        public bool $isGift,
        public ?string $ipAddress,
        public ?array $payload,
        public bool $walletUsage
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}

<?php

namespace App\Modules\Transaction\Events;

use App\Modules\Transaction\Models\PaymentMethod;
use App\Modules\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentMethodInitializationSuccess
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public User $user,
        public PaymentMethod $paymentMethod,
        public string $customerCode,
        public string $authorizationCode,
        public string $email,
        public string $currency,
        public string $externalTransactionReference,
        public int $expiryMonth,
        public int $expiryYear,
        public string $lastFour,
        public string $cardType,
        public string $bank,
        public string $brand,
        public ?string $accountName = null,
    ) {
        //
    }

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

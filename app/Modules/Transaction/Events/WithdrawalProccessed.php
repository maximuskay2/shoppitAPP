<?php

namespace App\Modules\Transaction\Events;

use App\Modules\Transaction\Models\Wallet;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WithdrawalProccessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Wallet $wallet,
        public float $amount,
        public float $fees,
        public string $currency,
        public ?string $reference,
        public ?string $external_transaction_reference,
        public ?string $narration,
        public ?string $ip_address,
        public ?array $payload,
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

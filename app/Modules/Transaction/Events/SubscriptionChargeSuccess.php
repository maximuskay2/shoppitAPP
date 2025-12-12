<?php

namespace App\Modules\Transaction\Events;

use App\Modules\Transaction\Models\SubscriptionRecord;
use App\Modules\User\Models\Vendor;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionChargeSuccess
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Vendor $vendor,
        public SubscriptionRecord $record,
        public string $plan_code,
        public string $customer_code,
        public string $authorization_code,
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

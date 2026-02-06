<?php

namespace App\Modules\Commerce\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverNotificationBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $driverId,
        public string $type,
        public array $payload = [],
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('driver.notifications.' . $this->driverId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'driver.notification';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'payload' => $this->payload,
        ];
    }
}

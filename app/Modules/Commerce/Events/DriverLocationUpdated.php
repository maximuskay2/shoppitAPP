<?php

namespace App\Modules\Commerce\Events;

use App\Modules\User\Models\DriverLocation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DriverLocation $location,
        public ?string $orderId = null,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin.fleet.locations'),
        ];

        if ($this->orderId) {
            $channels[] = new PrivateChannel('order.tracking.' . $this->orderId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'driver.location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'driver_id' => $this->location->user_id,
            'lat' => $this->location->lat,
            'lng' => $this->location->lng,
            'bearing' => $this->location->bearing,
            'recorded_at' => $this->location->recorded_at,
            'order_id' => $this->orderId,
        ];
    }
}

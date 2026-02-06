<?php

namespace App\Modules\Commerce\Events;

use App\Modules\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public User $driverUser) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.fleet.locations'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'driver.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'driver_id' => $this->driverUser->id,
            'is_online' => $this->driverUser->driver?->is_online,
            'updated_at' => $this->driverUser->updated_at,
        ];
    }
}

<?php

namespace App\Modules\Commerce\Notifications\Driver;

use App\Modules\Commerce\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class OrderReadyForPickupNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->push_in_app_notifications ?? true) {
            $channels[] = FCMChannel::class;
        }

        return $channels;
    }

    public function databaseType(object $notifiable): string
    {
        return 'order.ready_for_pickup';
    }

    public function toFCM(object $notifiable): CloudMessage
    {
        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => 'Order ready for pickup',
                'body' => 'A new order is ready for pickup.',
            ])
            ->withData([
                'notification_key' => 'order.ready_for_pickup',
                'order_id' => $this->order->id,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Order ready for pickup',
            'message' => 'A new order is ready for pickup.',
            'data' => [
                'order_id' => $this->order->id,
                'vendor_id' => $this->order->vendor_id,
            ],
        ];
    }
}

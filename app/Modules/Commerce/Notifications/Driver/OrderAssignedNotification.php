<?php

namespace App\Modules\Commerce\Notifications\Driver;

use App\Modules\Commerce\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class OrderAssignedNotification extends Notification implements ShouldQueue
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
        return 'order.assigned';
    }

    public function toFCM(object $notifiable): CloudMessage
    {
        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => 'Order assigned',
                'body' => 'A new order has been assigned to you.',
            ])
            ->withData([
                'notification_key' => 'order.assigned',
                'order_id' => $this->order->id,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Order assigned',
            'message' => 'A new order has been assigned to you.',
            'data' => [
                'order_id' => $this->order->id,
                'vendor_id' => $this->order->vendor_id,
            ],
        ];
    }
}

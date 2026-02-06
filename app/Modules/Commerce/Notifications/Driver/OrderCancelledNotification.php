<?php

namespace App\Modules\Commerce\Notifications\Driver;

use App\Modules\Commerce\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class OrderCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order, public ?string $reason = null) {}

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
        return 'order.cancelled';
    }

    public function toFCM(object $notifiable): CloudMessage
    {
        $body = 'An assigned order was cancelled.';
        if ($this->reason) {
            $body = $body . ' Reason: ' . $this->reason;
        }

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => 'Order cancelled',
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'order.cancelled',
                'order_id' => $this->order->id,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Order cancelled',
            'message' => $this->reason ? 'Order cancelled: ' . $this->reason : 'An assigned order was cancelled.',
            'data' => [
                'order_id' => $this->order->id,
                'reason' => $this->reason,
            ],
        ];
    }
}

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

    /**
     * The number of times the job may be attempted
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job
     *
     * @var int
     */
    public array $backoff = [60, 300, 900];

    public function __construct(public Order $order, public ?string $reason = null)
    {
        $this->onQueue('notifications');
    }

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

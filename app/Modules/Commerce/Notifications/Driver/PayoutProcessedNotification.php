<?php

namespace App\Modules\Commerce\Notifications\Driver;

use App\Modules\Transaction\Models\DriverPayout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class PayoutProcessedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DriverPayout $payout) {}

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
        return 'payout.processed';
    }

    public function toFCM(object $notifiable): CloudMessage
    {
        $amount = $this->payout->amount->getAmount()->toFloat();

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => 'Payout processed',
                'body' => 'Your payout of ' . $amount . ' has been processed.',
            ])
            ->withData([
                'notification_key' => 'payout.processed',
                'payout_id' => $this->payout->id,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Payout processed',
            'message' => 'Your payout has been processed.',
            'data' => [
                'payout_id' => $this->payout->id,
                'amount' => $this->payout->amount->getAmount()->toFloat(),
            ],
        ];
    }
}

<?php

namespace App\Modules\Commerce\Notifications\Driver;

use App\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class AccountVerifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public User $driver)
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
        return 'account.verified';
    }

    public function toFCM(object $notifiable): CloudMessage
    {
        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => 'Account verified',
                'body' => 'Your driver account has been verified. You can now go online.',
            ])
            ->withData([
                'notification_key' => 'account.verified',
                'driver_id' => $this->driver->id,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Account verified',
            'message' => 'Your driver account has been verified. You can now go online.',
            'data' => [
                'driver_id' => $this->driver->id,
            ],
        ];
    }
}

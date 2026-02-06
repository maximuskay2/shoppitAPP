<?php

namespace App\Modules\Commerce\Notifications\Driver;

use App\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class AccountBlockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $driver, public ?string $reason = null) {}

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
        return 'account.blocked';
    }

    public function toFCM(object $notifiable): CloudMessage
    {
        $body = 'Your driver account has been blocked.';
        if ($this->reason) {
            $body = $body . ' Reason: ' . $this->reason;
        }

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => 'Account blocked',
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'account.blocked',
                'driver_id' => $this->driver->id,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Account blocked',
            'message' => $this->reason ? 'Account blocked: ' . $this->reason : 'Your driver account has been blocked.',
            'data' => [
                'driver_id' => $this->driver->id,
                'reason' => $this->reason,
            ],
        ];
    }
}

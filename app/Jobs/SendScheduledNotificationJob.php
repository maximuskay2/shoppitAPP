<?php

namespace App\Jobs;

use App\Modules\User\Models\ScheduledNotification;
use App\Modules\User\Models\User;
use App\Modules\User\Notifications\AdminBroadcastNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendScheduledNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $scheduledNotificationId) {}

    public function handle(): void
    {
        $notification = ScheduledNotification::find($this->scheduledNotificationId);
        if (!$notification || $notification->status !== 'pending') {
            Log::warning('Scheduled notification not found or already sent', ['id' => $this->scheduledNotificationId]);
            return;
        }
        $user = User::find($notification->user_id);
        if (!$user) {
            Log::warning('User not found for scheduled notification', ['user_id' => $notification->user_id]);
            $notification->status = 'failed';
            $notification->save();
            return;
        }
        $notif = new AdminBroadcastNotification($notification->title, $notification->body, $notification->data ?? []);
        $user->notify($notif);
        $notification->status = 'sent';
        $notification->save();
    }
}

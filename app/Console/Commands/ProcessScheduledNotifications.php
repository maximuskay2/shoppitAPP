<?php

namespace App\Console\Commands;

use App\Modules\User\Models\ScheduledNotification;
use App\Jobs\SendScheduledNotificationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessScheduledNotifications extends Command
{
    protected $signature = 'notifications:process-scheduled';
    protected $description = 'Process and dispatch scheduled notifications';

    public function handle()
    {
        $now = Carbon::now();
        $pending = ScheduledNotification::where('status', 'pending')
            ->where('scheduled_at', '<=', $now)
            ->get();
        foreach ($pending as $notification) {
            dispatch(new SendScheduledNotificationJob($notification->id));
            $this->info("Dispatched scheduled notification: {$notification->id}");
        }
        $this->info('Scheduled notifications processing complete.');
    }
}

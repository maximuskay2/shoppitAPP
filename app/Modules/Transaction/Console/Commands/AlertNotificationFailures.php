<?php

namespace App\Modules\Transaction\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AlertNotificationFailures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:alert-notification-failures';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Alert when FCM notification failure rate exceeds threshold';

    public function handle(): int
    {
        $total = (int) Cache::get('metrics:notifications:fcm:total', 0);
        $failed = (int) Cache::get('metrics:notifications:fcm:failed', 0);

        $minTotal = (int) env('NOTIFICATION_FAILURE_MIN_TOTAL', 10);
        $threshold = (float) env('NOTIFICATION_FAILURE_RATE_THRESHOLD', 0.1);
        $cooldownMinutes = (int) env('NOTIFICATION_FAILURE_ALERT_COOLDOWN_MINUTES', 10);

        Cache::put('alerts:notifications:last_run', now()->toISOString(), now()->addHours(2));
        Cache::put('alerts:notifications:last_total', $total, now()->addHours(2));
        Cache::put('alerts:notifications:last_failed', $failed, now()->addHours(2));

        if ($total < $minTotal) {
            Cache::put('alerts:notifications:last_rate', 0, now()->addHours(2));
            return Command::SUCCESS;
        }

        $rate = $total > 0 ? $failed / $total : 0;
        Cache::put('alerts:notifications:last_rate', $rate, now()->addHours(2));

        if ($rate < $threshold) {
            return Command::SUCCESS;
        }

        $cooldownKey = 'alerts:notifications:last_alert_at';
        if (Cache::has($cooldownKey)) {
            return Command::SUCCESS;
        }

        Cache::put($cooldownKey, now()->toISOString(), now()->addMinutes($cooldownMinutes));

        if (config('logging.channels.slack.url')) {
            Log::channel('slack')->error('FCM notification failure rate exceeded', [
                'failed' => $failed,
                'total' => $total,
                'rate' => round($rate, 4),
                'threshold' => $threshold,
            ]);
        }

        return Command::SUCCESS;
    }
}

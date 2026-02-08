<?php

namespace App\Listeners;

use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Cache;

class NotificationMetricsListener
{
    private const WINDOW_MINUTES = 15;

    public function handle(NotificationSent|NotificationFailed $event): void
    {
        if (!$this->isFcmChannel($event->channel)) {
            return;
        }

        $totalKey = 'metrics:notifications:fcm:total';
        $failedKey = 'metrics:notifications:fcm:failed';
        $expiresAt = now()->addMinutes(self::WINDOW_MINUTES);

        Cache::add($totalKey, 0, $expiresAt);
        Cache::add($failedKey, 0, $expiresAt);

        Cache::increment($totalKey);

        if ($event instanceof NotificationFailed) {
            Cache::increment($failedKey);
        }
    }

    private function isFcmChannel(string $channel): bool
    {
        return $channel === 'fcm' || str_contains($channel, 'FCM');
    }
}

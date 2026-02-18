<?php

namespace App\Services;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class FcmNotificationService
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_SECONDS = 10;

    /**
     * Send notification with retry logic for FCM channel
     */
    public static function sendWithRetry($notifiable, Notification $notification): bool
    {
        $attempt = 0;
        $success = false;
        $lastError = null;
        while ($attempt < self::MAX_RETRIES && !$success) {
            try {
                NotificationFacade::send($notifiable, $notification);
                $success = true;
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::warning('FCM notification failed', [
                    'attempt' => $attempt + 1,
                    'error' => $lastError,
                ]);
                sleep(self::RETRY_DELAY_SECONDS);
            }
            $attempt++;
        }
        if (!$success) {
            Log::error('FCM notification failed after retries', [
                'error' => $lastError,
            ]);
        }
        return $success;
    }
}

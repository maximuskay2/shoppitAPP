<?php

namespace App\Modules\Transaction\Listeners;

use App\Modules\Transaction\Events\SubscriptionExpiringCards;
use App\Modules\Transaction\Notifications\SubscriptionExpiringCardsNotification;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SubscriptionExpiringCardsListener implements ShouldQueue
{
    public function __construct()
    {
        //
    }

    public function handle(SubscriptionExpiringCards $event): void
    {
        $subscription = $event->subscription;
        $expiryDate = $event->expiryDate;
        $cardBrand = $event->cardBrand;
        $cardDescription = $event->cardDescription;
        $nextPaymentDate = $event->nextPaymentDate;
        $planName = $event->planName;
        
        Log::info('SubscriptionExpiringCardsListener.handle() :', [
            'subscription_id' => $subscription->id,
            'expiry_date' => $event->expiryDate,
            'card_brand' => $event->cardBrand,
        ]);

        Cache::lock("subscription:{$subscription->id}", 10)->block(5, function () use ($subscription, $expiryDate, $cardBrand, $cardDescription, $nextPaymentDate, $planName) {
            try {
                $subscription->vendor->user->notify(new SubscriptionExpiringCardsNotification(
                    $subscription,
                    $expiryDate,
                    $cardBrand,
                    $cardDescription,
                    $nextPaymentDate,
                    $planName
                ));

                Log::info('Expiring card notification sent successfully', [
                    'user_id' => $subscription->vendor->user->id,
                    'subscription_id' => $subscription->id,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to send expiring card notification', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                throw $e;
            }
        });
    }
}

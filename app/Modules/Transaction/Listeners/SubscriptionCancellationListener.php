<?php

namespace App\Modules\Transaction\Listeners;

use App\Modules\Commerce\Services\SubscriptionService;
use App\Modules\Transaction\Enums\UserSubscriptionStatusEnum;
use App\Modules\Transaction\Events\SubscriptionCancellation;
use App\Modules\Transaction\Notifications\SubscriptionCancelledNotification;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionCancellationListener implements ShouldQueue
{
    public function __construct(
        public SubscriptionService $subscriptionService,
    ) {}

    public function handle(SubscriptionCancellation $event): void
    {
        $subscription = $event->subscription;

        Log::info('SubscriptionCancellationListener.handle() :', [
            'subscription' => $subscription,
        ]);

        Cache::lock("subscription:{$subscription->id}", 10)->block(5, function () use ($subscription) {
            try {
                DB::beginTransaction();

                $subscription->update([
                    'is_auto_renew' => false,
                    'canceled_at' => now(),
                    'status' => UserSubscriptionStatusEnum::CANCELLED,
                ]);

                $subscription->vendor->user->notify(new SubscriptionCancelledNotification($subscription->plan, $subscription));
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("SubscriptionCancellationListener.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

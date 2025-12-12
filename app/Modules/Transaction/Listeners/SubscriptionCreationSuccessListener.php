<?php

namespace App\Listeners\User\Wallet;

use App\Modules\Commerce\Services\SubscriptionService;
use App\Modules\Transaction\Events\SubscriptionCreationSuccess;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionCreationSuccessListener implements ShouldQueue
{
    public function __construct(
        public SubscriptionService $subscriptionService,
    ) {}

    public function handle(SubscriptionCreationSuccess $event): void
    {
        $subscription = $event->subscription;
        $customer_code = $event->customer_code;
        $subscription_code = $event->customer_code;

        Log::info('SubscriptionCreationSuccess.handle() :', [
            'subscription' => $subscription,
        ]);

        Cache::lock("subscription:{$subscription->id}", 10)->block(5, function () use ($subscription, $customer_code, $subscription_code) {
            try {
                DB::beginTransaction();

                $subscription->update([
                    'paystack_subscription_code' => $subscription_code,
                    'paystack_customer_code' => $customer_code,
                ]);

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("SubscriptionCreationSuccess.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

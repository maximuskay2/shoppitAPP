<?php

namespace App\Modules\Transaction\Listeners;

use App\Modules\Commerce\Services\SubscriptionService;
use App\Modules\Transaction\Enums\SubscriptionStatusEnum;
use App\Modules\Transaction\Enums\UserSubscriptionStatusEnum;
use App\Modules\Transaction\Events\SubscriptionDisabled;
use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\Transaction\Notifications\SubscriptionDisabledNotification;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionDisabledListener implements ShouldQueue
{
    public function __construct(
        public SubscriptionService $subscriptionService,
    ) {}

    public function handle(SubscriptionDisabled $event): void
    {
        $subscription = $event->subscription;

        Log::info('SubscriptionDisabledListener.handle() :', [
            'subscription' => $subscription,
        ]);

        Cache::lock("subscription:{$subscription->id}", 10)->block(5, function () use ($subscription) {
            try {
                DB::beginTransaction();
                $former_plan = $subscription->plan;

                $free_subscription = SubscriptionPlan::where('key', 1)
                    ->where('status', SubscriptionStatusEnum::ACTIVE)
                    ->first();

                $subscription->update([
                    'subscription_plan_id' => $free_subscription->id,
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                    'status' => UserSubscriptionStatusEnum::ACTIVE,
                ]);

                $subscription->user->notify(new SubscriptionDisabledNotification($former_plan, $subscription));
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("SubscriptionDisabledListener.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

<?php

namespace App\Modules\Transaction\Console\Commands;

use App\Modules\Commerce\Services\SubscriptionService;
use App\Modules\Transaction\Enums\SubscriptionStatusEnum;
use App\Modules\Transaction\Enums\UserSubscriptionStatusEnum;
use App\Modules\Transaction\Models\Subscription;
use App\Modules\Transaction\Models\SubscriptionPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SubscriptionRevertCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:subscription-revert-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscription command to revert user subscription to free plan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $free_subscription = SubscriptionPlan::where('key', 1)
            ->where('status', SubscriptionStatusEnum::ACTIVE)
            ->first();
                
        $subscriptions = Subscription::where('subscription_plan_id', '!=', $free_subscription->id)
            ->where('status', UserSubscriptionStatusEnum::EXPIRED)
            ->where('ends_at', '<=', now()->subDays(7))
            ->get();

        if(!$subscriptions->isEmpty()) {
            foreach ($subscriptions as $subscription) {
                try {
                    $subscriptionService = resolve(SubscriptionService::class);
                    $subscriptionService->revertSubscription($subscription);
                } catch (\Exception $e) {
                    // Log the error but continue processing other subscriptions
                    Log::error("Failed to revert subscription ID {$subscription->id}: " . $e->getMessage());
                    continue;
                }
            }
        }
    }
}

<?php

namespace App\Modules\Transaction\Console\Commands;

use App\Modules\Transaction\Enums\SubscriptionStatusEnum;
use App\Modules\Transaction\Enums\UserSubscriptionStatusEnum;
use App\Modules\Transaction\Models\Subscription;
use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\Transaction\Notifications\SubscriptionRevertReminderNotification;
use Illuminate\Console\Command;

class SubscriptionRevertReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:subscription-revert-reminder-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscription command to remind users that the subscription will revert to free plan';

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
            ->where('ends_at', '<=', now()->subDay()) // Already expired
            ->where('ends_at', '>=', now()->subDays(6))
            ->get();

        if(!$subscriptions->isEmpty()) {
            foreach ($subscriptions as $subscription) {
                try {
                    $subscription->vendor->user->notify(new SubscriptionRevertReminderNotification($free_subscription));
                } catch (\Exception $e) {
                    // Log the error but continue processing other subscriptions
                    continue;
                }
            }
        }
    }
}

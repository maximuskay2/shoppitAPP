<?php

namespace App\Modules\Transaction\Console\Commands;

use App\Modules\Transaction\Enums\SubscriptionStatusEnum;
use App\Modules\Transaction\Enums\UserSubscriptionStatusEnum;
use App\Modules\Transaction\Models\Subscription;
use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\Transaction\Notifications\SubscriptionReminderNotification;
use Illuminate\Console\Command;

class SubscriptionRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:subscription-reminders-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscription command to handle reminders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $free_subscription = SubscriptionPlan::where('key', 1)
            ->where('status', SubscriptionStatusEnum::ACTIVE)
            ->first();
                
        $subscriptions = Subscription::where('subscription_plan_id', '!=', $free_subscription->id)
            ->where(function ($query) {
                    $query->where('status', UserSubscriptionStatusEnum::ACTIVE)
                        ->orWhere('status', UserSubscriptionStatusEnum::CANCELLED);
                })
            ->where('ends_at', '<=', now()->addDays(3))
            ->where('ends_at', '>=', now())
            ->get();

        if(!$subscriptions->isEmpty()) {
            foreach ($subscriptions as $subscription) {
                try {
                    $subscription->user->notify(new SubscriptionReminderNotification($subscription, $subscription->plan));
                } catch (\Exception $e) {
                    // Log the error but continue processing other subscriptions
                    continue;
                }
            }
        }
    }
}

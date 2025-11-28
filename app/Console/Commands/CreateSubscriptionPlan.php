<?php

namespace App\Console\Commands;

use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\Transaction\Services\PaystackService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateSubscriptionPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:create-plan
                            {name : Plan name}
                            {amount : Amount in Naira (e.g., 2500 for ₦2,500)}
                            {--interval=monthly : Billing interval (daily, weekly, monthly, yearly)}
                            {--currency=NGN : Currency code}
                            {--features=* : Plan features (can be specified multiple times)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new subscription plan and sync with Paystack';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $paystackService = new PaystackService();

        try {
            // Get next available key
            $key = SubscriptionPlan::max('key') + 1;

            // Validate interval
            $validIntervals = ['daily', 'weekly', 'monthly', 'yearly'];
            $interval = $this->option('interval');
            if (!in_array($interval, $validIntervals)) {
                $this->error("Invalid interval. Must be one of: " . implode(', ', $validIntervals));
                return 1;
            }

            // Get features
            $features = $this->option('features');
            if (empty($features)) {
                $this->warn('No features specified. You can add features later or use --features option.');
                $features = [];
            }

            // Create plan in database
            $plan = SubscriptionPlan::create([
                'key' => $key,
                'name' => $this->argument('name'),
                'amount' => $this->argument('amount') * 100, // Convert to kobo
                'currency' => $this->option('currency'),
                'interval' => $interval,
                'features' => $features,
            ]);

            // Sync with Paystack immediately
            $paystackPlan = $paystackService->createPlan($plan);

            $this->info("✅ Subscription plan '{$plan->name}' created and synced with Paystack!");
            $this->line("Plan ID: {$plan->id}");
            $this->line("Key: {$plan->key}");
            $this->line("Amount: ₦" . number_format($this->argument('amount'), 0));
            $this->line("Interval: {$interval}");
            $this->line("Paystack Plan ID: {$plan->paystack_plan_id}");

            if (!empty($features)) {
                $this->line("Features:");
                foreach ($features as $feature) {
                    $this->line("  • {$feature}");
                }
            }

        } catch (\Exception $e) {
            $this->error("❌ Failed to create plan: " . $e->getMessage());
            Log::error('Command CreateSubscriptionPlan failed', [
                'error' => $e->getMessage(),
                'arguments' => $this->arguments(),
                'options' => $this->options(),
            ]);
            return 1;
        }
    }
}

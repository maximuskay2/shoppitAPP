<?php

namespace Database\Seeders;

use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\Transaction\Services\PaymentService;
use Brick\Money\Money;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubscriptionPlanSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'key' => 1,
                'name' => 'Tier 1 - Starter (Free)',
                'amount' => Money::of(0, 'NGN'), // Free plan
                'currency' => 'NGN',
                'interval' => 'monthly',
                'features' => [
                    'List up to 5 products per month',
                    'Standard visibility (shown below premium stores)',
                    'Back testing',
                    'Access to limited analytics (monthly summary only)'
                ]
            ],
            [
                'key' => 2,
                'name' => 'Tier 2 - Growth',
                'amount' => Money::of(3000, 'NGN'),
                'currency' => 'NGN',
                'interval' => 'monthly',
                'features' => [
                    'List up to 25 products per month',
                    'Priority placement in search results & category pages',
                    'Access to full sales analytics dashboard',
                    'Eligible for Shoppittplus promo campaigns and discount events',
                    'Faster product approval process'
                ]
            ],
            [
                'key' => 3,
                'name' => 'Tier 3 - Premium',
                'amount' => Money::of(7000, 'NGN'),
                'currency' => 'NGN',
                'interval' => 'monthly',
                'features' => [
                    'Unlimited product listings',
                    'Top-tier visibility across homepage & featured banners',
                    'Full analytics with insights, sales trends & conversion tracking',
                    'Lower transaction commission (e.g., 3% instead of 5%)',
                    'Eligible for homepage spotlight & vendor-of-the-week features'
                ]
            ]
        ];

        foreach ($plans as $planData) {
            try {
                // Check if plan already exists
                $existingPlan = SubscriptionPlan::where('key', $planData['key'])->first();

                if ($existingPlan) {
                    $this->command->info("Plan with key {$planData['key']} already exists. Skipping...");
                    continue;
                }
                
                // Create plan in database
                $planData['id'] = Str::uuid()->toString();
                $plan = SubscriptionPlan::create($planData);

                // Sync with Paystack immediately
                $paymentService = resolve(PaymentService::class);
                $paymentService->createPlan($plan);

                $this->command->info("Created plan: {$plan->name} (Key: {$plan->key})");
                $this->command->line("   Paystack Plan ID: {$plan->paystack_plan_id}");
                $this->command->line("   Features: " . count($plan->features) . " items");

                Log::info('Created subscription plan and synced with Paystack', [
                    'plan_id' => $plan->id,
                    'plan_key' => $plan->key,
                    'paystack_plan_id' => $plan->paystack_plan_id
                ]);

            } catch (\Exception $e) {
                $this->command->error("Failed to create plan: {$planData['name']} - " . $e->getMessage());

                Log::error('Failed to create/sync subscription plan in seeder', [
                    'plan_data' => $planData,
                    'error' => $e->getMessage()
                ]);

                // Continue with other plans instead of stopping
                continue;
            }
        }

        $this->command->info('Subscription plan seeding completed!');
    }
}

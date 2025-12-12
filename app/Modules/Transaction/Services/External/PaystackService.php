<?php

namespace App\Modules\Transaction\Services\External;

use App\Modules\Transaction\Events\PaystackChargeSuccessEvent;
use App\Modules\Transaction\Models\Subscription;
use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\Transaction\Models\SubscriptionRecord;
use App\Modules\User\Models\Vendor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\info;

class PaystackService
{
     /**
     * The base URL for Paystack API.
     *
     * @var string
     */
    private static $baseUrl;

    /**
     * PaystackService constructor.
     *
     * @param string $baseUrl The base URL for Paystack API.
     */
    public function __construct(string $baseUrl)
    {
        self::$baseUrl = $baseUrl;
    }

    /**
     * Create a plan on Paystack and update the local plan with paystack_plan_id
     */
    public function createPlan(SubscriptionPlan $plan)
    {
        $payload = [
            'name' => $plan->name,
            'amount' => $plan->amount->getAmount()->toInt() * 100, // Amount in kobo (smallest currency unit)
            'interval' => $plan->interval,
            'currency' => $plan->currency,
        ];

        $url = self::$baseUrl . '/plan';

        $response = Http::talkToPaystack($url, 'POST', $payload);

        $plan->update(['paystack_plan_id' => $response['data']['plan_code']]);
        return $response['data'];
    }

    /**
     * Subscribe a vendor to a plan
     */
    public function subscribe(Vendor $vendor, SubscriptionRecord $record, SubscriptionPlan $plan)
    {
        // First, ensure the plan exists on Paystack
        if (!$plan->paystack_plan_id) {
            $this->createPlan($plan);
        }

        $payload = [
            'amount' => $record->amount->getAmount()->toInt() * 100,
            'email' => $vendor->user->email,
            'plan' => $plan->paystack_plan_id,
        ];

        $url = self::$baseUrl . '/transaction/initialize';

        $response = Http::talkToPaystack($url, 'POST', $payload);

        $record->update([
            'processor_transaction_id' => $response['data']['reference']
        ]);

        return [
            'authorization_url' => $response['data']['authorization_url']
        ];
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Subscription $subscription)
    {
        $url = self::$baseUrl . '/subscription/' . $subscription->paystack_subscription_code . '/disable';

        $response = Http::talkToPaystack($url, 'POST');

        if ($response['status']) {
            $subscription->update([
                'is_active' => false,
                'canceled_at' => now(),
            ]);
            return true;
        }
        
        Log::error('Failed to cancel Paystack subscription', [
            'subscription_id' => $subscription->id,
            'response' => $response->body(),
        ]);

        throw new \Exception('Failed to cancel subscription on Paystack');
    }

    /**
     * Handle Paystack webhook
     */
    public function handleWebhook(array $payload)
    {
        $event = $payload['event'];

        switch ($event) {
            case 'charge.success':
                $this->handleChargeSuccess($payload['data']);
                break;
            case 'subscription.create':
                $this->handleSubscriptionCreated($payload['data']);
                break;
            case 'subscription.disable':
                $this->handleSubscriptionDisabled($payload['data']);
                break;
            case 'invoice.payment_failed':
                $this->handlePaymentFailed($payload['data']);
                break;
            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($payload['data']);
                break;
            default:
                Log::info('Unhandled Paystack webhook event', ['event' => $event]);
        }
    }

    protected function handleSubscriptionCreated(array $data)
    {
        $subscription = Subscription::where('paystack_subscription_code', $data['subscription_code'])->first();

        if ($subscription) {
            $subscription->update([
                'is_active' => true,
                'starts_at' => $data['createdAt'],
            ]);
        }
    }

    protected function handleSubscriptionDisabled(array $data)
    {
        $subscription = Subscription::where('paystack_subscription_code', $data['subscription_code'])->first();

        if ($subscription) {
            $subscription->update([
                'is_active' => false,
                'canceled_at' => now(),
            ]);
        }
    }

    protected function handlePaymentFailed(array $data)
    {
        $subscription = Subscription::where('paystack_subscription_code', $data['subscription_code'])->first();

        if ($subscription) {
            $subscription->update([
                'payment_failed_at' => now(),
                'failure_notification_count' => $subscription->failure_notification_count + 1,
                'last_failure_notification_at' => now(),
            ]);
        }
    }

    protected function handleChargeSuccess(array $data)
    {
        PaystackChargeSuccessEvent::dispatch($data);
        Log::info('PaystackChargeSuccessEvent dispatched', ['data' => $data]);
    }

    protected function handlePaymentSucceeded(array $data)
    {
        $subscription = Subscription::where('paystack_subscription_code', $data['subscription_code'])->first();

        if ($subscription) {
            // Update subscription end date based on plan interval
            $plan = $subscription->plan;
            $endsAt = $this->calculateNextBillingDate($subscription->ends_at ?? now(), $plan->interval);

            $subscription->update([
                'ends_at' => $endsAt,
                'payment_failed_at' => null,
            ]);
        }
    }

    protected function calculateNextBillingDate($currentDate, $interval)
    {
        $date = \Carbon\Carbon::parse($currentDate);

        switch ($interval) {
            case 'daily':
                return $date->addDay();
            case 'weekly':
                return $date->addWeek();
            case 'monthly':
                return $date->addMonth();
            case 'yearly':
                return $date->addYear();
            default:
                return $date->addMonth(); // Default to monthly
        }
    }    
}
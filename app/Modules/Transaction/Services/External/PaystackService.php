<?php

namespace App\Modules\Transaction\Services\External;

use App\Modules\Transaction\Events\PaystackChargeSuccessEvent;
use App\Modules\Transaction\Models\Subscription;
use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\User\Models\Vendor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            'amount' => $plan->amount, // Amount in kobo (smallest currency unit)
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
    public function subscribe(Vendor $vendor, SubscriptionPlan $plan, string $authorizationCode)
    {
        // First, ensure the plan exists on Paystack
        if (!$plan->paystack_plan_id) {
            $this->createPlan($plan);
        }

        $payload = [
            'customer' => $vendor->user->email,
            'plan' => $plan->paystack_plan_id,
            'authorization' => $authorizationCode,
        ];

        $url = self::$baseUrl . '/subscription';

        $response = Http::talkToPaystack($url, 'POST', $payload);

        $subscription = Subscription::updateOrCreate(
            ['vendor_id' => $vendor->id, 'subscription_plan_id' => $plan->id],
            [
                'paystack_subscription_code' => $response['data']['subscription_code'],
                'paystack_customer_code' => $response['data']['customer'],
                'starts_at' => now(),
                'is_active' => true,
            ]
        );

        return $subscription;
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
        \Log::info('PaystackChargeSuccessEvent dispatched', ['data' => $data]);
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

    /**
     * Verify webhook signature
     */
    public function verifyWebhook($payload, $signature)
    {
        $computedSignature = hash_hmac('sha512', $payload, $this->secretKey);

        \Log::info('Verifying Paystack webhook signature', [
            'computed' => $computedSignature,
            'received' => $signature,
        ]);

        return hash_equals($computedSignature, $signature);
    }
    
}
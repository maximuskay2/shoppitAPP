<?php

namespace App\Modules\Transaction\Services\External;

use App\Modules\Commerce\Models\Settings;
use App\Modules\Transaction\Enums\PartnersEnum;
use App\Modules\Transaction\Models\PaymentMethod;
use App\Modules\Transaction\Models\Subscription;
use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\Transaction\Models\SubscriptionRecord;
use App\Modules\User\Models\PaymentDetail;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Brick\Money\Money as BrickMoney;
use GPBMetadata\Google\Type\Money;
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
            'payment_processor' => PartnersEnum::PAYSTACK,
            'processor_transaction_id' => $response['data']['reference']
        ]);

        return [
            'authorization_url' => $response['data']['authorization_url']
        ];
    }

    public function upgradeSubscription(Vendor $vendor, SubscriptionRecord $record, SubscriptionPlan $plan)
    {
        $subscription = $vendor->subscription;
        $disablePayload = [
            'code' => $subscription->paystack_subscription_code,
            'token' => $vendor->user->email_token
        ];

        $disableUrl = self::$baseUrl . '/subscription/disable';

        $disableResponse = Http::talkToPaystack($disableUrl, 'POST', $disablePayload);

        if ($disableResponse['status'] === 'true' || $disableResponse['status'] === true) {
            // First, ensure the plan exists on Paystack
            if (!$plan->paystack_plan_id) {
                $this->createPlan($plan);
            }

            $record->update([
                'payment_processor' => PartnersEnum::PAYSTACK,
            ]);
    
            $payload = [
                'customer' => $vendor->user->customer_code,
                'plan' => $plan->paystack_plan_id,
            ];
    
            $url = self::$baseUrl . '/subscription';
    
            $response = Http::talkToPaystack($url, 'POST', $payload);
    
            return $response;
        }
    }

    public function updatePaymentMethod(Subscription $subscription)
    {
        $url = self::$baseUrl . '/subscription/' . $subscription->paystack_subscription_code . '/manage/link';
        
        $response = Http::talkToPaystack($url, 'GET');
        return $response['data'];
    }
    
    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Vendor $vendor, Subscription $subscription)
    {
        $url = self::$baseUrl . '/subscription/disable';

        $payload = [
            'code' => $subscription->paystack_subscription_code,
            'token' => $vendor->user->email_token
        ];

        Http::talkToPaystack($url, 'POST', $payload);
    }

    public function resumeSubscription(Vendor $vendor, Subscription $subscription)
    {
        $url = self::$baseUrl . '/subscription/enable';

        $payload = [
            'code' => $subscription->paystack_subscription_code,
            'token' => $vendor->user->email_token
        ];

        Http::talkToPaystack($url, 'POST', $payload);
    }

    public function initializePaymentMethod(User $user)
    {
        $payload = [
            'email' => $user->email,
            'channels' => ['card'],
            'amount' => BrickMoney::of(100, Settings::getValue('currency'))->getMinorAmount()->toInt(),
            'metadata' => [
                'type' => 'payment_method_initialization'
            ]
        ];

        $url = self::$baseUrl . '/transaction/initialize';

        $response = Http::talkToPaystack($url, 'POST', $payload);

        return $response['data'];
    }

    public function addFunds(User $user, int $amount, PaymentMethod $paymentMethod = null)
    {
        if (!is_null($paymentMethod)) {
            $payload = [
                'email' => $user->email,
                'amount' => $amount,
                'authorization_code' => $paymentMethod ? $paymentMethod->authorization_code : null,
                'metadata' => [
                    'type' => 'wallet_funding'
                ]
            ];
    
            $url = self::$baseUrl . '/charge';
            
            $response = Http::talkToPaystack($url, 'POST', $payload);
    
            return $response['data'];
        }
        
        $payload = [
            'email' => $user->email,
            'amount' => $amount,
            'metadata' => [
                'type' => 'wallet_funding'
            ]
        ];

        $url = self::$baseUrl . '/transaction/initialize';

        $response = Http::talkToPaystack($url, 'POST', $payload);

        return $response['data'];
    }
    
    public function withdrawFunds(int $amount, PaymentDetail $paymentDetail = null)
    {
        $payload = [
            'source' => 'balance',
            'amount' => $amount,
            'recipient' => $paymentDetail->paystack_recipient_code,
            'reason' => 'Wallet Withdrawal'
        ];

        $url = self::$baseUrl . '/transfer';

        $response = Http::talkToPaystack($url, 'POST', $payload);

        return $response['data'];
    }

    public function processOrder(User $user, int $amount, PaymentMethod $paymentMethod = null)
    {
        if (!is_null($paymentMethod)) {
            $payload = [
                'email' => $user->email,
                'amount' => $amount,
                'authorization_code' => $paymentMethod ? $paymentMethod->authorization_code : null,
                'metadata' => [
                    'type' => 'order_payment'
                ]
            ];
    
            $url = self::$baseUrl . '/charge';
            
            $response = Http::talkToPaystack($url, 'POST', $payload);
    
            return $response['data'];
        }
        
        $payload = [
            'email' => $user->email,
            'amount' => $amount,
            'metadata' => [
                'type' => 'order_payment'
            ]
        ];

        $url = self::$baseUrl . '/transaction/initialize';

        $response = Http::talkToPaystack($url, 'POST', $payload);

        return $response['data'];
    }

    public function listBanks()
    {
        $url = self::$baseUrl . '/bank?country=nigeria';

        $response = Http::talkToPaystack($url, 'GET');

        return $response;
    }

    public function resolveAccount(array $data)
    {
        $url = self::$baseUrl . '/bank/resolve?account_number=' . $data['account_number'] . '&bank_code=' . $data['bank_code'];
        $response = Http::talkToPaystack($url, 'GET');
        
        return $response['data'];
    }

    public function createTransferRecipient(array $data)
    {
        $payload = [
            'type' => 'nuban',
            'name' => $data['account_name'],
            'account_number' => $data['account_number'],
            'bank_code' => $data['bank_code'],
            'currency' => Settings::getValue('currency'),
        ];

        $url = self::$baseUrl . '/transferrecipient';

        $response = Http::talkToPaystack($url, 'POST', $payload);

        return $response['data'];
    }

    public function deleteTransferRecipient(string $recipientCode)
    {
        $url = self::$baseUrl . '/transferrecipient/' . $recipientCode;
        $response = Http::talkToPaystack($url, 'DELETE');

        return $response;
    }
}
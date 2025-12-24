<?php

namespace App\Modules\Transaction\Jobs;

use App\Events\User\Banking\ManualBankTransactionSyncSuccessfulEvent;
use App\Events\User\Services\ServiceProfit;
use App\Events\User\Subscription\SubscriptionFailedEvent;
use App\Events\User\Subscription\SubscriptionSuccessfulEvent;
use App\Events\User\Transactions\TransferFailed;
use App\Events\User\Transactions\TransferSuccessful;
use App\Events\User\Wallet\WalletTransactionReceived;
use App\Jobs\Webhook\ProcessSuccessfulOutwardTransfer;
use App\Models\Transaction;
use App\Modules\Commerce\Models\Settings;
use App\Modules\Transaction\Enums\PartnersEnum;
use App\Modules\Transaction\Events\FundWalletSuccessful;
use App\Modules\Transaction\Events\PaymentMethodInitializationSuccess;
use App\Modules\Transaction\Events\SubscriptionCancellation;
use App\Modules\Transaction\Events\SubscriptionChargeSuccess;
use App\Modules\Transaction\Events\SubscriptionCreationSuccess;
use App\Modules\Transaction\Events\SubscriptionDisabled;
use App\Modules\Transaction\Events\SubscriptionExpiringCards;
use App\Modules\Transaction\Events\SubscriptionInvoiceCreated;
use App\Modules\Transaction\Events\SubscriptionInvoicePaymentFailed;
use App\Modules\Transaction\Events\SubscriptionInvoiceUpdated;
use App\Modules\Transaction\Services\WebhookService;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Ramsey\Collection\Set;

class ProcessPaystackWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        protected array $payload,
        protected string $ipAddress
    ) {}

    public function handle(WebhookService $webhookService)
    {
        try {
            Log::info('Processing Paystack webhook in queue', ['payload' => $this->payload]);

            $responseData = ['message' => 'Webhook processed'];
            $webhookService->recordIncomingWebhook(
                PartnersEnum::PAYSTACK->value,
                $this->payload,
                $responseData,
                200,
                $this->ipAddress
            );

            $event_type = $this->payload['event'] ?? null;

            if (in_array($event_type, ['charge.success']) && strtolower($this->payload['data']['status']) === 'success' && !empty($this->payload['data']['plan'])) {
                $this->processSubscriptionChargeSuccess();
                return;
            }

            if (in_array($event_type, ['subscription.create']) && strtolower($this->payload['data']['status']) === 'active' && !empty($this->payload['data']['plan'])) {
                $this->processSubscriptionCreationSuccess();
                return;
            }

            if (in_array($event_type, ['subscription.not_renew']) && strtolower($this->payload['data']['status']) === 'non-renewing' && !empty($this->payload['data']['plan'])) {
                $this->processSubscriptionCancellation();
                return;
            }

            if (in_array($event_type, ['subscription.disable']) && strtolower($this->payload['data']['status']) === 'complete' && !empty($this->payload['data']['plan'])) {
                $this->processSubscriptionDisabled();
                return;
            }

            if (in_array($event_type, ['subscription.expiring_cards']) && !empty($this->payload['data'])) {
                $this->processSubscriptionExpiringCards();
                return;
            }

            if (in_array($event_type, ['invoice.create']) && !empty($this->payload['data'])) {
                $this->processInvoiceCreated();
                return;
            }

            if (in_array($event_type, ['invoice.update']) && strtolower($this->payload['data']['status']) === 'success' && !empty($this->payload['data']['subscription'])) {
                $this->processInvoiceUpdated();
                return;
            }

            if (in_array($event_type, ['invoice.payment_failed']) && !empty($this->payload['data']['subscription'])) {
                $this->processInvoicePaymentFailed();
                return;
            }

            if (in_array($event_type, ['charge.success']) && strtolower($this->payload['data']['status']) === 'success' && empty($this->payload['data']['plan']) && isset($this->payload['data']['metadata']['type']) && $this->payload['data']['metadata']['type'] === 'payment_method_initialization') {
                $this->processPaymentMethodInitializationSuccess();
                return;
            }

            if (in_array($event_type, ['charge.success']) && strtolower($this->payload['data']['status']) === 'success' && empty($this->payload['data']['plan']) && isset($this->payload['data']['metadata']['type']) && $this->payload['data']['metadata']['type'] === 'wallet_funding') {
                $this->processWalletFundingSuccess();
                return;
            }
        } catch (\Exception $e) {
            Log::error('Paystack Webhook Processing Failed', [
                'error' => $e->getMessage(),
                'payload' => $this->payload
            ]);
            throw $e;
        }
    }

    protected function processWalletFundingSuccess()
    {
        $external_transaction_reference = $this->payload['data']['reference'];
        $email = $this->payload['data']['customer']['email'];
        $fees = $this->payload['data']['fees'];
        $currency = $this->payload['data']['currency'];

        $user = User::where('email', $email)->firstOrFail();
        $transaction = $user->transactions()
            ->where([
                'external_transaction_reference' => $external_transaction_reference,
                'type' => 'FUND_WALLET'
            ])
            ->firstOrFail();

        Log::info('Processing Wallet Funding Success', [
            'external_transaction_reference' => $external_transaction_reference,
            'email' => $email,
            'currency' => $currency,
        ]);

        event(new FundWalletSuccessful($transaction, $fees / 100));
    }

    protected function processPaymentMethodInitializationSuccess()
    {
        $external_transaction_reference = $this->payload['data']['reference'];
        $expiry_month = $this->payload['data']['authorization']['exp_month'];
        $expiry_year = $this->payload['data']['authorization']['exp_year'];
        $last_four = $this->payload['data']['authorization']['last4'];
        $channel = $this->payload['data']['authorization']['channel'];
        $card_type = $this->payload['data']['authorization']['card_type'];
        $bank = $this->payload['data']['authorization']['bank'];
        $brand = $this->payload['data']['authorization']['brand'];
        $account_name = $this->payload['data']['authorization']['account_name'];
        $authorization_code = $this->payload['data']['authorization']['authorization_code'];
        $email = $this->payload['data']['customer']['email'];
        $customer_code = $this->payload['data']['customer']['customer_code'];
        $currency = $this->payload['data']['currency'];

        $user = User::where('email', $email)->firstOrFail();

        $paymentMethod = $user->paymentMethods()
            ->where([
                'reference' => $external_transaction_reference,
                'provider' => 'paystack',
                'method' => $channel
            ])
            ->firstOrFail();

        Log::info('Processing Payment Method Initialization Success', [
            'external_transaction_reference' => $external_transaction_reference,
            'email' => $email,
            'currency' => $currency,
        ]);

        event(new PaymentMethodInitializationSuccess($user, $paymentMethod, $customer_code, $authorization_code, $email, $currency, $external_transaction_reference, $expiry_month, $expiry_year, $last_four, $card_type, $bank, $brand, $account_name));
    }

    protected function processSubscriptionChargeSuccess()
    {
        $external_transaction_reference = $this->payload['data']['reference'];
        $plan_code = $this->payload['data']['plan']['plan_code'];
        $customer_code = $this->payload['data']['customer']['customer_code'];
        $authorization_code = $this->payload['data']['authorization']['authorization_code'];
        $email = $this->payload['data']['customer']['email'];
        $currency = $this->payload['data']['currency'];

        $vendor = Vendor::where('user_id', User::where('email', $email)->first()->id)->firstOrFail();

        $record = $vendor->subscription->records()
            ->where([
                'payment_processor' => PartnersEnum::PAYSTACK,
                'processor_transaction_id' => $external_transaction_reference
            ])
            ->firstOrFail();
        
        Log::info('Processing Subscription Charge Success', [
            'external_transaction_reference' => $external_transaction_reference,
            'plan_code' => $plan_code,
            'email' => $email,
            'currency' => $currency
        ]);

        event(new SubscriptionChargeSuccess($vendor, $record, $plan_code, $customer_code, $authorization_code, $email, $currency, $external_transaction_reference));
    }

    protected function processSubscriptionCreationSuccess()
    {
        $subscription_code = $this->payload['data']['subscription_code'];
        $email_token = $this->payload['data']['email_token'];
        $customer_code = $this->payload['data']['customer']['customer_code'];
        $email = $this->payload['data']['customer']['email'];

        $vendor = Vendor::where('user_id', User::where('email', $email)->first()->id)->firstOrFail();

        $subscription = $vendor->subscription;
        
        Log::info('Processing Subscription Creation Success', [
            'customer_code' => $customer_code,
            'email' => $email
        ]);

        event(new SubscriptionCreationSuccess($subscription, $subscription_code, $customer_code, $email_token));
    }

    protected function processSubscriptionCancellation()
    {
        $customer_code = $this->payload['data']['customer']['customer_code'];
        $email = $this->payload['data']['customer']['email'];
        $subscription_code = $this->payload['data']['subscription_code'];

        $vendor = Vendor::where('user_id', User::where('email', $email)->first()->id)->firstOrFail();

        $subscription = $vendor->subscription
            ->where('paystack_subscription_code', $subscription_code)
            ->firstOrFail();
        
        Log::info('Processing Subscription Cancellation Success', [
            'customer_code' => $customer_code,
            'email' => $email,
        ]);

        event(new SubscriptionCancellation($subscription));
    }

    protected function processSubscriptionDisabled()
    {
        $customer_code = $this->payload['data']['customer']['customer_code'];
        $email = $this->payload['data']['customer']['email'];
        $subscription_code = $this->payload['data']['subscription_code'];

        $vendor = Vendor::where('user_id', User::where('email', $email)->first()->id)->firstOrFail();

        $subscription = $vendor->subscription
            ->where('paystack_subscription_code', $subscription_code)
            ->firstOrFail();
        
        Log::info('Processing Subscription Disabled', [
            'customer_code' => $customer_code,
            'email' => $email,
        ]);

        event(new SubscriptionDisabled($subscription));
    }

    protected function processSubscriptionExpiringCards()
    {
        $cardData = $this->payload['data'];
        $email = $cardData['customer']['email'];
        $expiryDate = $cardData['expiry_date'];
        $cardBrand = $cardData['brand'];
        $cardDescription = $cardData['description'];
        $subscriptionCode = $cardData['subscription']['subscription_code'];
        $nextPaymentDate = $cardData['subscription']['next_payment_date'];
        $planName = $cardData['subscription']['plan']['name'];

        try {
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                Log::warning('User not found for expiring card', ['email' => $email]);
            }

            $vendor = Vendor::where('user_id', $user->id)->first();
            
            if (!$vendor || !$vendor->subscription) {
                Log::warning('Vendor or subscription not found', ['email' => $email]);
            }

            $subscription = $vendor->subscription;

            Log::info('Processing expiring card for user', [
                'email' => $email,
                'expiry_date' => $expiryDate,
                'subscription_code' => $subscriptionCode,
            ]);

            event(new SubscriptionExpiringCards(
                $subscription,
                $expiryDate,
                $cardBrand,
                $cardDescription,
                $nextPaymentDate,
                $planName
            ));
        } catch (\Exception $e) {
            Log::error('Failed to process expiring card', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function processInvoiceCreated()
    {
        $invoiceCode = $this->payload['data']['invoice_code'];
        $amount = $this->payload['data']['amount'];
        $periodStart = $this->payload['data']['period_start'];
        $periodEnd = $this->payload['data']['period_end'];
        $email = $this->payload['data']['customer']['email'];

        try {
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                Log::warning('User not found for invoice.create', ['email' => $email]);
                return;
            }

            $vendor = Vendor::where('user_id', $user->id)->first();
            
            if (!$vendor || !$vendor->subscription) {
                Log::warning('Vendor or subscription not found for invoice.create', ['email' => $email]);
                return;
            }

            event(new SubscriptionInvoiceCreated($vendor->subscription, $invoiceCode, $periodStart, $periodEnd, $amount / 100));
        } catch (\Exception $e) {
            Log::error('Failed to process invoice.create', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function processInvoiceUpdated()
    {
        $invoiceCode = $this->payload['data']['invoice_code'];
        $email = $this->payload['data']['customer']['email'];

        try {
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                Log::warning('User not found for invoice.update', ['email' => $email]);
                return;
            }

            $vendor = Vendor::where('user_id', $user->id)->first();
            
            if (!$vendor || !$vendor->subscription) {
                Log::warning('Vendor or subscription not found for invoice.update', ['email' => $email]);
                return;
            }

            $subscription = $vendor->subscription;

            // Find the subscription record by invoice code
            $record = $subscription->records()
                ->where([
                    'payment_processor' => PartnersEnum::PAYSTACK,
                    'processor_transaction_id' => $invoiceCode
                ])
                ->first();

            if (!$record) {
                Log::warning('Subscription record not found for invoice.update', [
                    'email' => $email,
                    'invoice_code' => $invoiceCode
                ]);
                return;
            }

            event(new SubscriptionInvoiceUpdated($subscription, $record, $invoiceCode));
        } catch (\Exception $e) {
            Log::error('Failed to process invoice.update', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function processInvoicePaymentFailed()
    {
        $invoiceCode = $this->payload['data']['invoice_code'];
        $email = $this->payload['data']['customer']['email'];

        try {
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                Log::warning('User not found for invoice.payment_failed', ['email' => $email]);
                return;
            }

            $vendor = Vendor::where('user_id', $user->id)->first();
            
            if (!$vendor || !$vendor->subscription) {
                Log::warning('Vendor or subscription not found for invoice.payment_failed', ['email' => $email]);
                return;
            }

            $subscription = $vendor->subscription;

            // Find the subscription record by invoice code
            $record = $subscription->records()
                ->where([
                    'payment_processor' => PartnersEnum::PAYSTACK,
                    'processor_transaction_id' => $invoiceCode
                ])
                ->first();

            if (!$record) {
                Log::warning('Subscription record not found for invoice.payment_failed', [
                    'email' => $email,
                    'invoice_code' => $invoiceCode
                ]);
                return;
            }

            event(new SubscriptionInvoicePaymentFailed(
                $subscription,
                $record,
                $invoiceCode,
            ));
        } catch (\Exception $e) {
            Log::error('Failed to process invoice.payment_failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
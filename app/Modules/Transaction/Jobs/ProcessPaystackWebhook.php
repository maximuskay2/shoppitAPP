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
use App\Models\Settings;
use App\Models\Transaction;
use App\Modules\Transaction\Enums\PartnersEnum;
use App\Modules\Transaction\Events\SubscriptionChargeSuccess;
use App\Modules\Transaction\Events\SubscriptionCreationSuccess;
use App\Modules\Transaction\Services\WebhookService;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

        } catch (\Exception $e) {
            Log::error('Paystack Webhook Processing Failed', [
                'error' => $e->getMessage(),
                'payload' => $this->payload
            ]);
            throw $e;
        }
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
        $customer_code = $this->payload['data']['customer']['customer_code'];
        $email = $this->payload['data']['customer']['email'];

        $vendor = Vendor::where('user_id', User::where('email', $email)->first()->id)->firstOrFail();

        $subscription = $vendor->subscription;
        
        Log::info('Processing Subscription Creation Success', [
            'customer_code' => $customer_code,
            'email' => $email,
        ]);

        event(new SubscriptionCreationSuccess($subscription, $subscription_code, $customer_code));
    }
}
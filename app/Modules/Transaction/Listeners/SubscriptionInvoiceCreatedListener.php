<?php

namespace App\Modules\Transaction\Listeners;

use App\Modules\Commerce\Models\Settings;
use App\Modules\Transaction\Enums\PartnersEnum;
use App\Modules\Transaction\Enums\SubscriptionRecordStatusEnum;
use App\Modules\Transaction\Events\SubscriptionInvoiceCreated;
use App\Modules\Transaction\Notifications\SubscriptionInvoiceCreatedNotification;
use Brick\Money\Money;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubscriptionInvoiceCreatedListener implements ShouldQueue
{
    public function __construct() {}

    public function handle(SubscriptionInvoiceCreated $event): void
    {
        $subscription = $event->subscription;
        $invoiceCode = $event->invoiceCode;
        $amount = $event->amount;
        $periodStart = $event->periodStart;
        $periodEnd = $event->periodEnd;

        Log::info('SubscriptionInvoiceCreatedListener.handle() :', [
            'subscription' => $subscription->id,
            'invoice_code' => $invoiceCode,
        ]);

        Cache::lock("invoice:create:{$invoiceCode}", 10)->block(5, function () use ($subscription, $invoiceCode, $amount, $periodStart, $periodEnd) {
            try {
                DB::beginTransaction();

                $vendor = $subscription->vendor;
                $user = $vendor->user;
                
                $record = $subscription->records()->create([
                    'subscription_id' => $subscription->id,
                    'subscription_plan_id' => $subscription->subscription_plan_id,
                    'amount' => Money::of($amount, $subscription->records()->first()->currency ?? Settings::where('currency', 'NGN')->first()->value),
                    'currency' => $subscription->records()->first()->currency ?? 'NGN',
                    'reference' => Str::uuid(),
                    'processor_transaction_id' => $invoiceCode,
                    'starts_at' => Carbon::parse($periodStart),
                    'ends_at' => Carbon::parse($periodEnd),
                    'status' => SubscriptionRecordStatusEnum::PENDING,
                    'payment_processor' => PartnersEnum::PAYSTACK,
                ]);

                $user->notify(new SubscriptionInvoiceCreatedNotification($subscription, $record, $subscription->plan->name));
                DB::commit();

                Log::info('SubscriptionInvoiceCreatedListener.handle() - Notification sent successfully', [
                    'subscription_id' => $subscription->id,
                    'invoice_code' => $invoiceCode
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("SubscriptionInvoiceCreatedListener.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

<?php

namespace App\Modules\Transaction\Listeners;

use App\Modules\Transaction\Enums\SubscriptionRecordStatusEnum;
use App\Modules\Transaction\Enums\UserSubscriptionStatusEnum;
use App\Modules\Transaction\Events\SubscriptionInvoicePaymentFailed;
use App\Modules\Transaction\Notifications\SubscriptionPaymentFailedNotification;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionInvoicePaymentFailedListener implements ShouldQueue
{
    public function __construct() {}

    public function handle(SubscriptionInvoicePaymentFailed $event): void
    {
        $subscription = $event->subscription;
        $record = $event->record;
        $invoiceCode = $event->invoiceCode;

        Log::info('SubscriptionInvoicePaymentFailedListener.handle() :', [
            'subscription' => $subscription->id,
            'invoice_code' => $invoiceCode,
            'record' => $record->id
        ]);

        Cache::lock("invoice:failed:{$invoiceCode}", 10)->block(5, function () use ($subscription, $record, $invoiceCode) {
            try {
                DB::beginTransaction();
                
                $vendor = $subscription->vendor;
                $user = $vendor->user;

                $record->update([
                    'status' => SubscriptionRecordStatusEnum::FAILED,
                ]);

                $subscription->update([
                    'status' => UserSubscriptionStatusEnum::EXPIRED,
                    'is_auto_renew' => false,
                    'payment_failed_at' => now(),
                ]);

                $user->notify(new SubscriptionPaymentFailedNotification($subscription, $record, $subscription->plan->name));
                
                DB::commit();

                Log::info('SubscriptionInvoicePaymentFailedListener.handle() - Payment failure handled', [
                    'subscription_id' => $subscription->id,
                    'invoice_code' => $invoiceCode
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("SubscriptionInvoicePaymentFailedListener.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

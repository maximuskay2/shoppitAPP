<?php

namespace App\Modules\Transaction\Listeners;

use App\Modules\Transaction\Enums\SubscriptionRecordStatusEnum;
use App\Modules\Transaction\Events\SubscriptionInvoiceUpdated;
use App\Modules\Transaction\Notifications\SubscriptionRenewedNotification;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionInvoiceUpdatedListener implements ShouldQueue
{
    public function __construct() {}

    public function handle(SubscriptionInvoiceUpdated $event): void
    {
        $subscription = $event->subscription;
        $record = $event->record;
        $invoiceCode = $event->invoiceCode;

        Log::info('SubscriptionInvoiceUpdatedListener.handle() :', [
            'subscription' => $subscription->id,
            'invoice_code' => $invoiceCode,
            'record' => $record->id
        ]);

        Cache::lock("invoice:update:{$invoiceCode}", 10)->block(5, function () use ($subscription, $record, $invoiceCode) {
            try {
                DB::beginTransaction();
                
                $vendor = $subscription->vendor;
                $user = $vendor->user;

                $record->update([
                    'status' => SubscriptionRecordStatusEnum::SUCCESSFUL,
                    'payload' => [
                        'renewal' => true
                    ]
                ]);

                $user->notify(new SubscriptionRenewedNotification($subscription, $record, $subscription->plan->name));
                
                DB::commit();

                Log::info('SubscriptionInvoiceUpdatedListener.handle() - Subscription renewed successfully', [
                    'subscription_id' => $subscription->id,
                    'invoice_code' => $invoiceCode
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("SubscriptionInvoiceUpdatedListener.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

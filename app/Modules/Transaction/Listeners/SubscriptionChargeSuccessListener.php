<?php

namespace App\Modules\Transaction\Listeners;

use App\Modules\Commerce\Services\SubscriptionService;
use App\Modules\Transaction\Enums\SubscriptionRecordStatusEnum;
use App\Modules\Transaction\Enums\UserSubscriptionStatusEnum;
use App\Modules\Transaction\Events\SubscriptionChargeSuccess;
use App\Modules\Transaction\Notifications\SubscriptionPaymentNotification;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionChargeSuccessListener implements ShouldQueue
{
    public function __construct(
        public SubscriptionService $subscriptionService,
    ) {}

    public function handle(SubscriptionChargeSuccess $event): void
    {
        $vendor = $event->vendor;
        $record = $event->record;
        $customer_code = $event->customer_code;
        $authorization_code = $event->authorization_code;

        Log::info('SubscriptionChargeSuccessListener.handle() :', [
            'record' => $record,
            'vendor' => $vendor
        ]);

        Cache::lock("record:{$record->id}", 10)->block(5, function () use ($record, $vendor, $customer_code, $authorization_code) {
            try {
                DB::beginTransaction();

                $user = $vendor->user;
                $subscription = $record->subscription;

                $record->update([
                    'status' => SubscriptionRecordStatusEnum::SUCCESSFUL,
                    'payload' => [
                        'renewal' => false
                    ]
                ]);
                
                $subscription->update([
                    'status' => UserSubscriptionStatusEnum::ACTIVE,
                ]);

                if (is_null($user->customer_code)) {
                    $user->update([
                        'customer_code' => $customer_code,
                        'authorization_code' => $authorization_code
                    ]);
                }

                $user->notify(new SubscriptionPaymentNotification($record, $record->subscriptionPlan));
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("SubscriptionChargeSuccessListener.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

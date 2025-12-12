<?php

namespace App\Modules\Commerce\Services;

use App\Enums\Subscription\ModelBillingEnum;
use App\Enums\Subscription\ModelPaymentMethodEnum;
use App\Enums\Subscription\ModelStatusEnum;
use App\Enums\Subscription\ModelUserStatusEnum;
use App\Modules\Commerce\Models\Settings;
use App\Modules\Transaction\Enums\SubscriptionRecordStatusEnum;
use App\Modules\Transaction\Enums\SubscriptionStatusEnum;
use App\Modules\Transaction\Enums\UserSubscriptionStatusEnum;
use App\Modules\Transaction\Models\Subscription;
use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\Transaction\Models\SubscriptionRecord;
use App\Modules\Transaction\Services\PaymentService;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Brick\Money\Money;
// use App\Notifications\User\Subscription\SubscriptionExpiredNotification;
// use App\Notifications\User\Subscription\SubscriptionRevertedNotification;
// use App\Notifications\User\Subscription\SubscriptionUpgradeNotification;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SubscriptionService
{  
    public $currency;
    public function __construct(private readonly PaymentService $paymentService)
    {
        $this->currency = Settings::where('name', 'currency')->first()->value;
    }

    public function getPlans()
    {
        return SubscriptionPlan::where('status', SubscriptionStatusEnum::ACTIVE)->orderBy('key', 'asc')->get();
    }

    public function fetchPlan(string $id)
    {
        return SubscriptionPlan::findOrFail($id);
    }

    public function fetchVendorSubscription(Vendor $vendor)
    {
        return Subscription::where('vendor_id', $vendor->id)->with(['plan'])->first();
    }
    
    public function createSubscription(Vendor $vendor, SubscriptionPlan $plan): Subscription
    {
        // Check if the vendor already has an active subscription
        $existingSubscription = Subscription::where('vendor_id', $vendor->id)
            ->where('subscription_plan_id', $plan->id)
            ->where('status', UserSubscriptionStatusEnum::ACTIVE)
            ->first();

        if ($existingSubscription) {
            throw new InvalidArgumentException("Vendor already has an active subscription for this plan.");
        }

        $subscription = Subscription::create([
            'vendor_id' => $vendor->id,
            'subscription_plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => UserSubscriptionStatusEnum::ACTIVE,
        ]);

        return $subscription->fresh();
    }

    public function subscribe (Vendor $vendor, array $data)
    {
        $plan = SubscriptionPlan::find($data['subscription_plan_id']);
        

        if (!$plan) {
            throw new InvalidArgumentException('Subscription plan not found');
        }

        $existingSubscription = $vendor->subscription;
        $free_subscription = SubscriptionPlan::where('key', 1)
            ->where('status', SubscriptionStatusEnum::ACTIVE)
            ->first();

        if (!is_null($existingSubscription) && $existingSubscription->status == UserSubscriptionStatusEnum::ACTIVE && $existingSubscription->subscription_plan_id !== $free_subscription->id) {
            throw new InvalidArgumentException("User already has an active subscription.");
        }

        try {
            DB::beginTransaction();

            $existingSubscription->update([
                'subscription_plan_id' => $data['subscription_plan_id'],
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'status' => UserSubscriptionStatusEnum::PENDING,
            ]);

            $record = $vendor->subscription->records()->create([
                'subscription_id' => $vendor->subscription->id,
                'subscription_plan_id' => $data['subscription_plan_id'],
                'amount' => Money::of($plan->amount->getAmount()->toInt(), $this->currency),
                'currency' => $this->currency,
                'reference' => Str::uuid(),
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
            ]);

            $response = $this->paymentService->subscribe($vendor, $record, $plan);
            
            DB::commit();
            return $response;
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new Exception("Failed to subscribe: " . $e->getMessage());
        }
        catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to subscribe: " . $e->getMessage());
        }

    }

    public function upgradeUserSubscription(User $user, array $data)
    {
        $subscription = $user->subscription;

        if (is_null($subscription) || $subscription->status !== ModelUserStatusEnum::ACTIVE) {
            throw new InvalidArgumentException("User does not have an active subscription.");
        }

        try {
            DB::beginTransaction();

            $subscription->update([
                'subscription_model_id' => $data['start'] === 'IMMEDIATE' ? $data['subscription_model_id'] : $subscription->subscription_model_id,
                'next_subscription_model_id' => $data['start'] === 'IMMEDIATE' ? null : $data['subscription_model_id'],
                'start_date' => $data['start'] === 'IMMEDIATE' ? now() : $subscription->start_date,
                'end_date' => $data['start'] === 'IMMEDIATE' ? ($data['billing'] == 'ANNUAL' ? now()->addMonths(12) : now()->addMonth()) : $subscription->end_date,
                'renewal_date' => $data['start'] === 'IMMEDIATE' ? ($data['billing'] == 'ANNUAL' ? now()->addMonths(12) : now()->addMonth()) : $subscription->renewal_date,
                'cancelled_at' => null,
                'status' => ModelUserStatusEnum::PENDING,
                'billing' => $data['billing'] == 'ANNUAL' ? ModelBillingEnum::ANNUAL : ModelBillingEnum::MONTHLY,
            ]);
            
            if ($data['start'] === 'IMMEDIATE') {
                $this->processPayment($user, $subscription, $data);
            } else {
               $user->notify(new SubscriptionUpgradeNotification(SubscriptionModel::find('subscription_model_id')));
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to upgrade subscription: " . $e->getMessage());
        }

    }

    private function processPayment(User $user, Subscription $subscription, array $data)
    {
        $model = $subscription->model;
        $amount = $data['billing'] == 'ANNUAL' ? $model->amount->multipliedBy(12) : $model->amount;
        $narration = "Subscribed for " . ucfirst($model->name->value) . " plan";
        if ($data['method'] == ModelPaymentMethodEnum::WALLET->value) {
            $transactionService = resolve(TransactionService::class);
            $transactionService->subscribe($user, $user->wallet->virtualBankAccount, $model, $amount->getAmount()->toFloat(), $narration, false, $data);
        }
    }

    public function cancelSubscription(User $user)
    {
        $subscription = $user->subscription;

        if (is_null($subscription) || $subscription->status !== ModelUserStatusEnum::ACTIVE) {
            throw new InvalidArgumentException("User does not have an active subscription.");
        }

        $subscription->update([
            'status' => ModelUserStatusEnum::CANCELLED,
            'cancelled_at' => now(),
            'is_auto_renew' => false,
        ]);
    }

    public function resumeSubscription(User $user)
    {
        $subscription = $user->subscription;

        if (is_null($subscription) || $subscription->status !== ModelUserStatusEnum::CANCELLED) {
            throw new InvalidArgumentException("User does not have a cancelled subscription.");
        }

        $subscription->update([
            'status' => ModelUserStatusEnum::ACTIVE,
            'cancelled_at' => null,
            'is_auto_renew' => true,
        ]);
    }

    public function expireSubscription(Subscription $subscription)
    {
        if (is_null($subscription)) {
            throw new InvalidArgumentException("User does not have a subscription.");
        }

        try {
            DB::beginTransaction();

            $subscription->update([
                'status' => ModelUserStatusEnum::EXPIRED,
            ]);

            DB::commit();
            $subscription->user->notify(new SubscriptionExpiredNotification($subscription->model));
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to expire subscription: " . $e->getMessage());
        }
    }
    
    public function autoRenewSubscription(Subscription $subscription)
    {
        if (is_null($subscription) || !$subscription->is_auto_renew) {
            throw new \InvalidArgumentException('Auto renew is disabled for this subscription');
        }

        try {
            DB::beginTransaction();
                
            $subscription->update([
                'subscription_model_id' => !is_null($subscription->next_subscription_model_id) ? $subscription->next_subscription_model_id : $subscription->subscription_model_id,
                'next_subscription_model_id' => null,
                'start_date' => now(),
                'end_date' => $subscription->billing == ModelBillingEnum::ANNUAL  ? now()->addMonths(12) : now()->addMonth(),
                'renewal_date' => $subscription->billing == ModelBillingEnum::ANNUAL  ? now()->addMonths(12) : now()->addMonth(),
                'status' => ModelUserStatusEnum::PENDING,
            ]);

            $this->processPayment(User::find($subscription->user->id), $subscription, ['method' => $subscription->method, 'billing' => $subscription->billing == ModelBillingEnum::ANNUAL ? 'ANNUAL' : 'MONTHLY']);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to auto renew subscription: " . $e->getMessage());
        }
    }
    
    public function renewSubscription(User $user, array $data)
    {
        $subscription = $user->subscription;
        if (is_null($subscription) || $subscription->status !== ModelUserStatusEnum::EXPIRED) {
            throw new InvalidArgumentException("User does not have an expired subscription.");
        }
        
        try {
            DB::beginTransaction();
            $subscription->update([
                'start_date' => $subscription->end_date,
                'end_date' => $subscription->end_date->addMonth(),
                'renewal_date' => $subscription->end_date->addMonth(),
            ]);
            
            $subscription->update([
                'subscription_model_id' => !is_null($subscription->next_subscription_model_id) ? $subscription->next_subscription_model_id : $subscription->subscription_model_id,
                'next_subscription_model_id' => null,
                'start_date' => now(),
                'end_date' => $data['billing'] == 'ANNUAL' ? now()->addMonths(12) : now()->addMonth(),
                'renewal_date' => $data['billing'] == 'ANNUAL' ? now()->addMonths(12) : now()->addMonth(),
                'status' => ModelUserStatusEnum::PENDING,
            ]);
            
            $this->processPayment($user, $subscription, $data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to renew subscription: " . $e->getMessage());
        }
    }

    public function revertSubscription(Subscription $subscription)
    {
        if (is_null($subscription)) {
            throw new InvalidArgumentException("User does not have a subscription.");
        }
    
        try {
            DB::beginTransaction();
            
            $free_subscription = SubscriptionModel::where('serial', 1)
                ->where('status', ModelStatusEnum::ACTIVE)
                ->first();
                
            $subscription->update([
                'subscription_model_id' => $free_subscription->id,
                'next_subscription_model_id' => null,
                'start_date' => now(),
                'end_date' => now()->addMonth(),
                'renewal_date' => now()->addMonth(),
                'status' => ModelUserStatusEnum::ACTIVE,
            ]);
            
            //delete the things needed, sub accounts, linked account etc
            resolve(BankAccountService::class)->revertLinkedBankAccount($subscription->user, $free_subscription);
            resolve(UserService::class)->revertSubAccounts($subscription->user, $free_subscription);
            DB::commit();
            
            $subscription->user->notify(new SubscriptionRevertedNotification($subscription->model));
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to auto revert subscription: " . $e->getMessage());
        }
    }
}

<?php

namespace App\Modules\Commerce\Services;

use App\Modules\Commerce\Models\Settings;
use App\Modules\Transaction\Enums\SubscriptionStatusEnum;
use App\Modules\Transaction\Enums\UserSubscriptionStatusEnum;
use App\Modules\Transaction\Models\Subscription;
use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\Transaction\Notifications\SubscriptionResumedNotification;
use App\Modules\Transaction\Notifications\SubscriptionRevertedNotification;
use App\Modules\Transaction\Services\PaymentService;
use App\Modules\User\Models\Vendor;
use App\Notifications\User\Subscription\SubscriptionExpiredNotification;
use Brick\Money\Money;
use Exception;
use Illuminate\Support\Facades\DB;
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

    public function upgradeSubscription(Vendor $vendor, array $data)
    {
        $subscription = $vendor->subscription;
        $plan = SubscriptionPlan::find($data['subscription_plan_id']);

        if (is_null($subscription) || $subscription->status !== UserSubscriptionStatusEnum::ACTIVE) {
            throw new InvalidArgumentException("Vendor does not have an active subscription.");
        }

        if ($plan->amount->isLessThan(SubscriptionPlan::find($subscription->subscription_plan_id)->amount)) {
            throw new InvalidArgumentException("Cannot downgrade subscription using upgrade endpoint.");
        }
        
        try {
            DB::beginTransaction();

            $subscription->update([
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

            $response = $this->paymentService->upgradeSubscription($vendor, $record, $plan);
            $subscription->update([
                'status' => UserSubscriptionStatusEnum::ACTIVE,
                'paystack_subscription_code' => $response['data']['subscription_code'] ?? null,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to upgrade subscription: " . $e->getMessage());
        }

    }

    public function updatePaymentMethod(Vendor $vendor)
    {
        $subscription = $vendor->subscription;

        if (is_null($subscription) || $subscription->status !== UserSubscriptionStatusEnum::ACTIVE) {
            throw new InvalidArgumentException("Vendor does not have an active subscription.");
        }

        try {
            $response = $this->paymentService->updatePaymentMethod($subscription);

            return $response;
        } catch (\Exception $e) {
            throw new Exception("Failed to update subscription payment method: " . $e->getMessage());
        }

    }

    public function cancelSubscription(Vendor $vendor)
    {
        $subscription = $vendor->subscription;
        $free_subscription = SubscriptionPlan::where('key', 1)
            ->where('status', SubscriptionStatusEnum::ACTIVE)
            ->first();

        if (is_null($subscription) || $subscription->status !== UserSubscriptionStatusEnum::ACTIVE || $subscription->subscription_plan_id === $free_subscription->id) {
            throw new InvalidArgumentException("Vendor does not have an active paid subscription.");
        }

        try {
            $this->paymentService->cancelSubscription($vendor, $subscription);
        } catch (InvalidArgumentException $e) {
            throw new Exception("Failed to subscribe: " . $e->getMessage());
        }
        catch (\Exception $e) {
            throw new Exception("Failed to subscribe: " . $e->getMessage());
        }
    }

    public function resumeSubscription(Vendor $vendor)
    {
        $subscription = $vendor->subscription;

        if (is_null($subscription) || $subscription->status !== UserSubscriptionStatusEnum::CANCELLED) {
            throw new InvalidArgumentException("Vendor does not have a cancelled subscription.");
        }

        try {
            DB::beginTransaction();
            $this->paymentService->resumeSubscription($vendor, $subscription);

            $subscription->update([
                'status' => UserSubscriptionStatusEnum::ACTIVE,
                'cancelled_at' => null,
                'is_auto_renew' => true,
            ]);
            $vendor->user->notify(new SubscriptionResumedNotification($subscription->plan, $subscription));
            DB::commit();
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new Exception("Failed to subscribe: " . $e->getMessage());
        }
        catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to subscribe: " . $e->getMessage());
        }
    }

    public function expireSubscription(Subscription $subscription)
    {
        if (is_null($subscription)) {
            throw new InvalidArgumentException("User does not have a subscription.");
        }

        try {
            DB::beginTransaction();

            $subscription->update([
                'status' => UserSubscriptionStatusEnum::EXPIRED,
            ]);

            DB::commit();
            $subscription->user->notify(new SubscriptionExpiredNotification($subscription->subscriptionPlan));
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to expire subscription: " . $e->getMessage());
        }
    }

    public function revertSubscription(Subscription $subscription)
    {
        if (is_null($subscription)) {
            throw new InvalidArgumentException("Vendor does not have a subscription.");
        }
    
        try {
            DB::beginTransaction();
            
            $free_subscription = SubscriptionPlan::where('key', 1)
                ->where('status', SubscriptionStatusEnum::ACTIVE)
                ->first();
                
            $subscription->update([
                'subscription_plan_id' => $free_subscription->id,
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'status' => UserSubscriptionStatusEnum::ACTIVE,
            ]);
            
            //delete the things needed, sub accounts, linked account etc
            // resolve(BankAccountService::class)->revertLinkedBankAccount($subscription->user, $free_subscription);
            // resolve(UserService::class)->revertSubAccounts($subscription->user, $free_subscription);
            DB::commit();
            
            $subscription->user->notify(new SubscriptionRevertedNotification($subscription->model));
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to auto revert subscription: " . $e->getMessage());
        }
    }
}

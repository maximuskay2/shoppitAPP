<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vendor\SubscribeRequest;
use App\Http\Resources\Transaction\SubscriptionPlanResource;
use App\Modules\Commerce\Services\SubscriptionService;
use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\Transaction\Services\External\PaystackService;
use App\Modules\User\Models\Vendor;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly PaystackService $paystackService,
        private readonly SubscriptionService $subscriptionService,
    ) {}

    /**
     * Get all subscription plans with current plan info
     */
    public function getPlans(): JsonResponse
    {
        try {
            $plans = $this->subscriptionService->getPlans();

            return ShopittPlus::response(true, 'Subscription plans retrieved successfully', 200, [
                'plans' => $plans
            ]);
        } catch (Exception $e) {
            Log::error('GET SUBSCRIPTION PLANS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve subscription plans', 500);
        }
    }

    public function fetchPlan(string $id): JsonResponse
    {
        try {
            $plan = $this->subscriptionService->fetchPlan($id);
            
            return ShopittPlus::response(true, 'Subscription plan retrieved successfully', 200, [
                'plan' => $plan
            ]);
        } catch (ModelNotFoundException | NotFoundHttpException $e) {
            Log::error('VENDOR: SHOW SUBSCRIPTION PLAN: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Cannot find Subscription Plan', 404);
        } catch (Exception $e) {
            Log::error('VENDOR: SHOW SUBSCRIPTION PLAN: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 500);
        }
    }
    
     public function fetchVendorSubscription(): JsonResponse
    {
        try {
            $vendor = Vendor::where('user_id', Auth::id())->first();

            $response = $this->subscriptionService->fetchVendorSubscription($vendor);
            return ShopittPlus::response(true, 'Vendor subscription fetched successfully', 200, $response);
        } catch (InvalidArgumentException $e) {
            Log::error('FETCH VENDOR SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('FETCH VENDOR SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to fetch vendor subscription', 500);
        }
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $vendor = Vendor::where('user_id', Auth::id())->first();

            $response = $this->subscriptionService->subscribe($vendor, $validatedData);
            return ShopittPlus::response(true, 'Vendor subscription proccessed successfully', 200, $response);
        } catch (Exception $e) {
            Log::error('SUBSCRIBE TO PLAN: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create subscription', 500);
        }
    }

    public function upgradeSubscription(SubscribeRequest $request): JsonResponse
    {
        try {
            $vendor = Vendor::where('user_id', Auth::id())->first();

            $this->subscriptionService->upgradeSubscription($vendor, $request->validated());

            return ShopittPlus::response(true, 'Vendor subscription upgraded successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('UPGRADE VENDOR SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPGRADE VENDOR SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to upgrade vendor subscription', 500);
        }
    }

    public function updatePaymentMethod(): JsonResponse
    {
        try {
            $vendor = Vendor::where('user_id', Auth::id())->first();

            $response = $this->subscriptionService->updatePaymentMethod($vendor);

            return ShopittPlus::response(true, 'Subscription payment method updated successfully', 200, $response);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE PAYMENT METHOD: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPDATE PAYMENT METHOD: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update subscription payment method', 500);
        }
    }

    public function cancelSubscription(): JsonResponse
    {
        try {
            $vendor = Vendor::where('user_id', Auth::id())->first();

            $this->subscriptionService->cancelSubscription($vendor);
            return ShopittPlus::response(true, 'Vendor subscription cancelled successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('CANCEL VENDOR SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('CANCEL VENDOR SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to cancel vendor subscription', 500);
        }
    }

    // This apparently doens't work with Paystack Subscriptions
    
    // public function resumeSubscription(): JsonResponse
    // {
    //     try {
    //         $vendor = Vendor::where('user_id', Auth::id())->first();

    //         $this->subscriptionService->resumeSubscription($vendor);

    //         return ShopittPlus::response(true, 'Vendor subscription resumed successfully', 200);
    //     } catch (InvalidArgumentException $e) {
    //         Log::error('RESUME VENDOR SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
    //         return ShopittPlus::response(false, $e->getMessage(), 400);
    //     } catch (Exception $e) {
    //         Log::error('RESUME VENDOR SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
    //         return ShopittPlus::response(false, 'Failed to resume vendor subscription', 500);
    //     }
    // }
}
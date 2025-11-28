<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vendor\SubscribeRequest;
use App\Http\Resources\Transaction\SubscriptionPlanResource;
use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\Transaction\Services\PaystackService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly PaystackService $paystackService
    ) {}

    /**
     * Get all subscription plans with current plan info
     */
    public function getPlans(): JsonResponse
    {
        try {
            $user = Auth::user();
            $vendor = $user->vendor;

            $plans = SubscriptionPlan::orderBy('key')->get();

            // Get current active subscription
            $currentSubscription = $vendor->subscriptions()
                ->with('plan')
                ->where('is_active', true)
                ->first();

            $currentPlan = null;
            if ($currentSubscription && $currentSubscription->plan) {
                $currentPlan = [
                    'tier' => $currentSubscription->plan->name,
                    'level' => "Current level"
                ];
            } else {
                // Default to free plan if no active subscription
                $freePlan = $plans->first(fn($plan) => $plan->amount === 0);
                if ($freePlan) {
                    $currentPlan = [
                        'tier' => $freePlan->name,
                        'level' => "Current level"
                    ];
                }
            }

            $response = [
                'currentPlan' => $currentPlan,
                'plans' => SubscriptionPlanResource::collection($plans)
            ];

            return ShopittPlus::response(
                true,
                'Subscription plans retrieved successfully',
                200,
                (object) $response
            );
        } catch (Exception $e) {
            Log::error('GET SUBSCRIPTION PLANS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve subscription plans', 500);
        }
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();
            $vendor = $user->vendor;

            $plan = SubscriptionPlan::find($validatedData['plan_id']);

            if (!$plan) {
                return ShopittPlus::response(false, 'Subscription plan not found', 404);
            }

            // Check if vendor already has an active subscription
            $existingSubscription = $vendor->subscriptions()
                ->where('subscription_plan_id', $plan->id)
                ->where('is_active', true)
                ->first();

            if ($existingSubscription) {
                return ShopittPlus::response(false, 'You already have an active subscription to this plan', 400);
            }

            $subscription = $this->paystackService->subscribe(
                $vendor,
                $plan,
                $validatedData['authorization_code']
            );

            return ShopittPlus::response(
                true,
                'Subscription created successfully',
                201,
                (object) ["subscription" => $subscription]
            );
        } catch (Exception $e) {
            Log::error('SUBSCRIBE TO PLAN: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create subscription', 500);
        }
    }
}
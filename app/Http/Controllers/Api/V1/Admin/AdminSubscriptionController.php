<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class AdminSubscriptionController extends Controller
{
    /**
     * Create a new AdminSubscriptionController instance.
     */
    public function __construct(
        protected AdminSubscriptionService $adminSubscriptionService,
    ) {}

    /**
     * Get all subscriptions with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $subscriptions = $this->adminSubscriptionService->getSubscriptions($request->all());
            return ShopittPlus::response(true, 'Subscriptions retrieved successfully', 200, $subscriptions);
        } catch (Exception $e) {
            Log::error('ADMIN GET SUBSCRIPTIONS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve subscriptions', 500);
        }
    }

    /**
     * Get a single subscription
     */
    public function show(string $id): JsonResponse
    {
        try {
            $subscription = $this->adminSubscriptionService->getSubscription($id);
            return ShopittPlus::response(true, 'Subscription retrieved successfully', 200, $subscription);
        } catch (Exception $e) {
            if ($e->getMessage() === 'Subscription not found') {
                return ShopittPlus::response(false, $e->getMessage(), 404);
            }
            Log::error('ADMIN GET SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve subscription', 500);
        }
    }

    /**
     * Get all subscriptions for a specific user
     */
    public function userSubscriptions(Request $request, string $userId): JsonResponse
    {
        try {
            $subscriptions = $this->adminSubscriptionService->getUserSubscriptions($userId, $request->all());
            return ShopittPlus::response(true, 'User subscriptions retrieved successfully', 200, $subscriptions);
        } catch (Exception $e) {
            Log::error('ADMIN GET USER SUBSCRIPTIONS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve user subscriptions', 500);
        }
    }
}
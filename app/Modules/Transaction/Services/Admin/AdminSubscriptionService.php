<?php

namespace App\Modules\Transaction\Services\Admin;

use App\Modules\Transaction\Models\Subscription;
use Exception;
use Illuminate\Support\Facades\Log;

class AdminSubscriptionService
{
    /**
     * Get all subscriptions with filters
     */
    public function getSubscriptions(array $filters = []): mixed
    {
        try {
            $query = Subscription::with(['vendor.user', 'plan'])
                ->orderBy('created_at', 'desc');

            // Apply search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->whereHas('vendor.user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'LIKE', '%' . $search . '%')
                                  ->orWhere('email', 'LIKE', '%' . $search . '%')
                                  ->orWhere('username', 'LIKE', '%' . $search . '%');
                    })
                    ->orWhereHas('plan', function ($planQuery) use ($search) {
                        $planQuery->where('name', 'LIKE', '%' . $search . '%');
                    })
                    ->orWhereHas('vendor', function ($vendorQuery) use ($search) {
                        $vendorQuery->where('business_name', 'LIKE', '%' . $search . '%');
                    })
                    ;
                });
            }

            // Apply status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Apply billing filter
            if (isset($filters['plan']) && !empty($filters['plan'])) {
                $query->whereHas('plan', function ($planQuery) use ($filters) {
                    $planQuery->where('interval', $filters['plan']);
                });
            }

            // Apply date range filters
            if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date']) && !empty($filters['end_date'])) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            }

            // Paginate results
            $perPage = $filters['per_page'] ?? 15;
            $subscriptions = $query->paginate($perPage);

            $formattedSubscriptions = $subscriptions->getCollection()->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'user' => $subscription->vendor->user ? $subscription->vendor->business_name ?? $subscription->vendor->user->name : null,
                    'user_email' => $subscription->vendor->user ? $subscription->vendor->user->email : null,
                    'tier' => $subscription->plan ? $subscription->plan->name : null,
                    'plan' => $subscription->plan ? $subscription->plan->interval : null,
                    'amount' => $subscription->plan ? $subscription->plan->amount->getAmount()->toFloat() : null,
                    'status' => $subscription->status->value,
                    'starts_at' => $subscription->starts_at?->format('Y-m-d'),
                    'ends_at' => $subscription->ends_at?->format('Y-m-d'),
                    'created_at' => $subscription->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $subscriptions->setCollection($formattedSubscriptions);

            return $subscriptions;
        } catch (Exception $e) {
            Log::error('ADMIN SUBSCRIPTION SERVICE - GET SUBSCRIPTIONS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single subscription with detailed information
     */
    public function getSubscription(string $id): array
    {
        try {
            $subscription = Subscription::with(['vendor.user', 'plan', 'records'])
                ->find($id);

            if (!$subscription) {
                throw new Exception('Subscription not found');
            }

            return [
                'id' => $subscription->id,
                'vendor' => $subscription->vendor ? [
                    'id' => $subscription->vendor->id,
                    'business_name' => $subscription->vendor->business_name,
                    'kyb_status' => $subscription->vendor->kyb_status,
                    'user' => $subscription->vendor->user ? [
                        'id' => $subscription->vendor->user->id,
                        'name' => $subscription->vendor->user->name,
                        'email' => $subscription->vendor->user->email,
                        'username' => $subscription->vendor->user->username,
                    ] : null,
                ] : null,
                'plan' => $subscription->plan ? [
                    'id' => $subscription->plan->id,
                    'name' => $subscription->plan->name,
                    'amount' => $subscription->plan->amount->getAmount()->toFloat(),
                    'interval' => $subscription->plan->interval,
                    'key' => $subscription->plan->key,
                    'features' => $subscription->plan->features,
                ] : null,
                'starts_at' => $subscription->starts_at?->format('Y-m-d H:i:s'),
                'ends_at' => $subscription->ends_at?->format('Y-m-d H:i:s'),
                'canceled_at' => $subscription->canceled_at?->format('Y-m-d H:i:s'),
                'payment_failed_at' => $subscription->payment_failed_at?->format('Y-m-d H:i:s'),
                'last_failure_notification_at' => $subscription->last_failure_notification_at?->format('Y-m-d H:i:s'),
                'status' => $subscription->status->value,
                'benefits_suspended' => $subscription->benefits_suspended,
                'paystack_subscription_code' => $subscription->paystack_subscription_code,
                'paystack_customer_code' => $subscription->paystack_customer_code,
                'records_count' => $subscription->records->count(),
                'created_at' => $subscription->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $subscription->updated_at->format('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            Log::error('ADMIN SUBSCRIPTION SERVICE - GET SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all subscriptions for a specific user
     */
    public function getUserSubscriptions(string $userId, array $filters = []): mixed
    {
        try {
            $query = Subscription::with(['plan', 'records'])
                ->whereHas('vendor', function ($vendorQuery) use ($userId) {
                    $vendorQuery->where('user_id', $userId);
                })
                ->orderBy('created_at', 'desc');

            // Apply status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Paginate results
            $perPage = $filters['per_page'] ?? 15;
            $subscriptions = $query->paginate($perPage);

            $formattedSubscriptions = $subscriptions->getCollection()->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'plan' => $subscription->plan ? $subscription->plan->name : null,
                    'interval' => $subscription->plan ? $subscription->plan->interval : null,
                    'status' => $subscription->status->value,
                    'starts_at' => $subscription->starts_at?->format('Y-m-d'),
                    'ends_at' => $subscription->ends_at?->format('Y-m-d'),
                    'is_auto_renew' => $subscription->is_auto_renew,
                    'records_count' => $subscription->records->count(),
                    'created_at' => $subscription->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $subscriptions->setCollection($formattedSubscriptions);

            return $subscriptions;
        } catch (Exception $e) {
            Log::error('ADMIN SUBSCRIPTION SERVICE - GET USER SUBSCRIPTIONS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }
}
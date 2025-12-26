<?php

namespace App\Services\Admin;

use App\Models\Subscription;
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
            $query = Subscription::with(['user', 'model'])
                ->orderBy('created_at', 'desc');

            // Apply search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'LIKE', '%' . $search . '%')
                                  ->orWhere('email', 'LIKE', '%' . $search . '%')
                                  ->orWhere('username', 'LIKE', '%' . $search . '%');
                    });
                });
            }

            // Apply status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Apply billing filter
            if (isset($filters['billing']) && !empty($filters['billing'])) {
                $query->where('billing', $filters['billing']);
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
                    'user' => $subscription->user ? $subscription->user->name : null,
                    'user_email' => $subscription->user ? $subscription->user->email : null,
                    'model_name' => $subscription->model ? $subscription->model->name : null,
                    'billing' => $subscription->billing->value,
                    'status' => $subscription->status->value,
                    'start_date' => $subscription->start_date?->format('Y-m-d'),
                    'end_date' => $subscription->end_date?->format('Y-m-d'),
                    'is_auto_renew' => $subscription->is_auto_renew,
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
            $subscription = Subscription::with(['user', 'model', 'payments'])
                ->find($id);

            if (!$subscription) {
                throw new Exception('Subscription not found');
            }

            return [
                'id' => $subscription->id,
                'user' => $subscription->user ? [
                    'id' => $subscription->user->id,
                    'name' => $subscription->user->name,
                    'email' => $subscription->user->email,
                    'username' => $subscription->user->username,
                ] : null,
                'model' => $subscription->model ? [
                    'id' => $subscription->model->id,
                    'name' => $subscription->model->name->value,
                    'amount' => $subscription->model->amount->getAmount()->toFloat(),
                    'billing' => $subscription->billing->value,
                    'features' => $subscription->model->features,
                ] : null,
                'payment_method' => $subscription->method->value,
                'billing' => $subscription->billing->value,
                'start_date' => $subscription->start_date?->format('Y-m-d H:i:s'),
                'end_date' => $subscription->end_date?->format('Y-m-d H:i:s'),
                'renewal_date' => $subscription->renewal_date?->format('Y-m-d H:i:s'),
                'cancelled_at' => $subscription->cancelled_at?->format('Y-m-d H:i:s'),
                'status' => $subscription->status->value,
                'is_auto_renew' => $subscription->is_auto_renew,
                'metadata' => $subscription->metadata,
                'payments_count' => $subscription->payments->count(),
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
            $query = Subscription::with(['model', 'payments'])
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc');

            // Apply status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Apply billing filter
            if (isset($filters['billing']) && !empty($filters['billing'])) {
                $query->where('billing', $filters['billing']);
            }

            // Paginate results
            $perPage = $filters['per_page'] ?? 15;
            $subscriptions = $query->paginate($perPage);

            $formattedSubscriptions = $subscriptions->getCollection()->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'model_name' => $subscription->model ? $subscription->model->name : null,
                    'billing' => $subscription->billing->value,
                    'status' => $subscription->status->value,
                    'start_date' => $subscription->start_date?->format('Y-m-d'),
                    'end_date' => $subscription->end_date?->format('Y-m-d'),
                    'is_auto_renew' => $subscription->is_auto_renew,
                    'payments_count' => $subscription->payments->count(),
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
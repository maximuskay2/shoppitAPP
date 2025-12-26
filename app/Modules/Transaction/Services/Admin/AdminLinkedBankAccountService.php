<?php

namespace App\Services\Admin;

use App\Models\LinkedBankAccount;
use Exception;
use Illuminate\Support\Facades\Log;

class AdminLinkedBankAccountService
{
    /**
     * Get all linked bank accounts with filters
     */
    public function getLinkedBankAccounts(array $filters = []): mixed
    {
        try {
            $query = LinkedBankAccount::with(['user'])
                ->orderBy('created_at', 'desc');

            // Apply search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('account_number', 'LIKE', '%' . $search . '%')
                      ->orWhere('account_name', 'LIKE', '%' . $search . '%')
                      ->orWhere('bank_name', 'LIKE', '%' . $search . '%')
                      ->orWhereHas('user', function ($userQuery) use ($search) {
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

            // Apply provider filter
            if (isset($filters['provider']) && !empty($filters['provider'])) {
                $query->where('provider', $filters['provider']);
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
            $linkedBankAccounts = $query->paginate($perPage);

            $formattedLinkedBankAccounts = $linkedBankAccounts->getCollection()->map(function ($lba) {
                return [
                    'id' => $lba->id,
                    'user' => $lba->user ? [
                        'id' => $lba->user->id,
                        'name' => $lba->user->name,
                        'email' => $lba->user->email,
                        'username' => $lba->user->username,
                    ] : null,
                    'account_number' => $lba->account_number,
                    'account_name' => $lba->account_name,
                    'bank_name' => $lba->bank_name,
                    'bank_code' => $lba->bank_code,
                    'type' => $lba->type,
                    'status' => $lba->status->value,
                    'provider' => $lba->provider,
                    'currency' => $lba->currency,
                    'country' => $lba->country,
                    'balance' => $lba->balance->getAmount()->toFloat(),
                    'created_at' => $lba->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $lba->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            $linkedBankAccounts->setCollection($formattedLinkedBankAccounts);

            return $linkedBankAccounts;
        } catch (Exception $e) {
            Log::error('ADMIN LINKED BANK ACCOUNT SERVICE - GET LINKED BANK ACCOUNTS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single linked bank account with detailed information
     */
    public function getLinkedBankAccount(string $id): array
    {
        try {
            $linkedBankAccount = LinkedBankAccount::with(['user'])->find($id);

            if (!$linkedBankAccount) {
                throw new Exception('Linked bank account not found');
            }

            return [
                'id' => $linkedBankAccount->id,
                'user' => $linkedBankAccount->user ? [
                    'id' => $linkedBankAccount->user->id,
                    'name' => $linkedBankAccount->user->name,
                    'email' => $linkedBankAccount->user->email,
                    'username' => $linkedBankAccount->user->username,
                    'status' => $linkedBankAccount->user->status,
                ] : null,
                'account_id' => $linkedBankAccount->account_id,
                'customer' => $linkedBankAccount->customer,
                'reference' => $linkedBankAccount->reference,
                'account_number' => $linkedBankAccount->account_number,
                'account_name' => $linkedBankAccount->account_name,
                'bank_name' => $linkedBankAccount->bank_name,
                'bank_code' => $linkedBankAccount->bank_code,
                'type' => $linkedBankAccount->type,
                'data_status' => $linkedBankAccount->data_status,
                'status' => $linkedBankAccount->status->value,
                'auth_method' => $linkedBankAccount->auth_method,
                'provider' => $linkedBankAccount->provider,
                'currency' => $linkedBankAccount->currency,
                'country' => $linkedBankAccount->country,
                'balance' => $linkedBankAccount->balance->getAmount()->toFloat(),
                'created_at' => $linkedBankAccount->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $linkedBankAccount->updated_at->format('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            Log::error('ADMIN LINKED BANK ACCOUNT SERVICE - GET LINKED BANK ACCOUNT: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all linked bank accounts for a specific user
     */
    public function getUserLinkedBankAccounts(string $userId, array $filters = []): mixed
    {
        try {
            $query = LinkedBankAccount::where('user_id', $userId)
                ->orderBy('created_at', 'desc');

            // Apply status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Apply provider filter
            if (isset($filters['provider']) && !empty($filters['provider'])) {
                $query->where('provider', $filters['provider']);
            }

            // Paginate results
            $perPage = $filters['per_page'] ?? 15;
            $linkedBankAccounts = $query->paginate($perPage);

            $formattedLinkedBankAccounts = $linkedBankAccounts->getCollection()->map(function ($lba) {
                return [
                    'id' => $lba->id,
                    'account_number' => $lba->account_number,
                    'account_name' => $lba->account_name,
                    'bank_name' => $lba->bank_name,
                    'bank_code' => $lba->bank_code,
                    'type' => $lba->type,
                    'status' => $lba->status->value,
                    'provider' => $lba->provider,
                    'currency' => $lba->currency,
                    'country' => $lba->country,
                    'balance' => $lba->balance->getAmount()->toFloat(),
                    'created_at' => $lba->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $linkedBankAccounts->setCollection($formattedLinkedBankAccounts);

            return $linkedBankAccounts;
        } catch (Exception $e) {
            Log::error('ADMIN LINKED BANK ACCOUNT SERVICE - GET USER LINKED BANK ACCOUNTS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }
}
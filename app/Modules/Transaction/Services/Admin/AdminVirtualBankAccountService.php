<?php

namespace App\Services\Admin;

use App\Models\VirtualBankAccount;
use Exception;
use Illuminate\Support\Facades\Log;

class AdminVirtualBankAccountService
{
    /**
     * Get all virtual bank accounts with filters
     */
    public function getVirtualBankAccounts(array $filters = []): mixed
    {
        try {
            $query = VirtualBankAccount::with(['wallet.user'])
                ->orderBy('created_at', 'desc');

            // Apply search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('account_number', 'LIKE', '%' . $search . '%')
                      ->orWhere('account_name', 'LIKE', '%' . $search . '%')
                      ->orWhere('bank_name', 'LIKE', '%' . $search . '%')
                      ->orWhereHas('wallet.user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'LIKE', '%' . $search . '%')
                                    ->orWhere('email', 'LIKE', '%' . $search . '%')
                                    ->orWhere('username', 'LIKE', '%' . $search . '%');
                      });
                });
            }

            // Apply provider filter
            if (isset($filters['provider']) && !empty($filters['provider'])) {
                $query->where('provider', $filters['provider']);
            }

            // Apply currency filter
            if (isset($filters['currency']) && !empty($filters['currency'])) {
                $query->where('currency', $filters['currency']);
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
            $virtualBankAccounts = $query->paginate($perPage);

            $formattedVirtualBankAccounts = $virtualBankAccounts->getCollection()->map(function ($vba) {
                return [
                    'id' => $vba->id,
                    'user' => $vba->wallet && $vba->wallet->user ? $vba->wallet->user->name : null,
                    'user_email' => $vba->wallet && $vba->wallet->user ? $vba->wallet->user->email : null,
                    'account_number' => $vba->account_number,
                    'account_name' => $vba->account_name,
                    'bank_name' => $vba->bank_name,
                    'bank_code' => $vba->bank_code,
                    'currency' => $vba->currency,
                    'provider' => $vba->provider,
                    'country' => $vba->country,
                    'created_at' => $vba->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $virtualBankAccounts->setCollection($formattedVirtualBankAccounts);

            return $virtualBankAccounts;
        } catch (Exception $e) {
            Log::error('ADMIN VIRTUAL BANK ACCOUNT SERVICE - GET VIRTUAL BANK ACCOUNTS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single virtual bank account with detailed information
     */
    public function getVirtualBankAccount(string $id): array
    {
        try {
            $virtualBankAccount = VirtualBankAccount::with(['wallet.user'])
                ->find($id);

            if (!$virtualBankAccount) {
                throw new Exception('Virtual bank account not found');
            }

            return [
                'id' => $virtualBankAccount->id,
                'user' => $virtualBankAccount->wallet && $virtualBankAccount->wallet->user ? [
                    'id' => $virtualBankAccount->wallet->user->id,
                    'name' => $virtualBankAccount->wallet->user->name,
                    'email' => $virtualBankAccount->wallet->user->email,
                    'username' => $virtualBankAccount->wallet->user->username,
                ] : null,
                'wallet' => $virtualBankAccount->wallet ? [
                    'id' => $virtualBankAccount->wallet->id,
                    'balance' => $virtualBankAccount->wallet->amount->getAmount()->toFloat(),
                    'currency' => $virtualBankAccount->wallet->currency,
                ] : null,
                'account_number' => $virtualBankAccount->account_number,
                'account_name' => $virtualBankAccount->account_name,
                'bank_name' => $virtualBankAccount->bank_name,
                'bank_code' => $virtualBankAccount->bank_code,
                'currency' => $virtualBankAccount->currency,
                'provider' => $virtualBankAccount->provider,
                'country' => $virtualBankAccount->country,
                'account_reference' => $virtualBankAccount->account_reference,
                'barter_id' => $virtualBankAccount->barter_id,
                'created_at' => $virtualBankAccount->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $virtualBankAccount->updated_at->format('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            Log::error('ADMIN VIRTUAL BANK ACCOUNT SERVICE - GET VIRTUAL BANK ACCOUNT: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all virtual bank accounts for a specific user
     */
    public function getUserVirtualBankAccounts(string $userId, array $filters = []): mixed
    {
        try {
            $vba = VirtualBankAccount::with(['wallet'])
                ->whereHas('wallet', function ($walletQuery) use ($userId) {
                    $walletQuery->where('user_id', $userId);
                })
                ->orderBy('created_at', 'desc')
                ->first();

                return [
                    'id' => $vba->id,
                    'account_number' => $vba->account_number,
                    'account_name' => $vba->account_name,
                    'bank_name' => $vba->bank_name,
                    'bank_code' => $vba->bank_code,
                    'currency' => $vba->currency,
                    'provider' => $vba->provider,
                    'country' => $vba->country,
                    'created_at' => $vba->created_at->format('Y-m-d H:i:s'),
                ];
        } catch (Exception $e) {
            Log::error('ADMIN VIRTUAL BANK ACCOUNT SERVICE - GET USER VIRTUAL BANK ACCOUNTS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }
}
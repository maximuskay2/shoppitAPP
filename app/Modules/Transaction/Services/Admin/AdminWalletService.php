<?php

namespace App\Services\Admin;

use App\Models\User\Wallet;
use Exception;
use Illuminate\Support\Facades\Log;

class AdminWalletService
{
    /**
     * Get all wallets with filters
     */
    public function getWallets(array $filters = []): mixed
    {
        try {
            $query = Wallet::with(['user', 'virtualBankAccount', 'transactions', 'walletTransactions'])
                ->orderBy('created_at', 'desc');

            // Apply search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'LIKE', '%' . $search . '%')
                                  ->orWhere('email', 'LIKE', '%' . $search . '%')
                                  ->orWhere('username', 'LIKE', '%' . $search . '%');
                    })
                    ->orWhereHas('virtualBankAccount', function ($vbaQuery) use ($search) {
                        $vbaQuery->where('account_number', 'LIKE', '%' . $search . '%')
                                 ->orWhere('account_name', 'LIKE', '%' . $search . '%');
                    });
                });
            }

            // Apply currency filter
            if (isset($filters['currency']) && !empty($filters['currency'])) {
                $query->where('currency', $filters['currency']);
            }

            // Apply active status filter
            if (isset($filters['is_active'])) {
                $query->where('is_active', (bool) $filters['is_active']);
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
            $wallets = $query->paginate($perPage);

            $formattedWallets = $wallets->getCollection()->map(function ($wallet) {
                return [
                    'id' => $wallet->id,
                    'user' => $wallet->user ? [
                        'id' => $wallet->user->id,
                        'name' => $wallet->user->name,
                        'email' => $wallet->user->email,
                        'username' => $wallet->user->username,
                    ] : null,
                    'balance' => $wallet->amount->getAmount()->toFloat(),
                    'ledger_balance' => $wallet->ledger_balance->getAmount()->toFloat(),
                    'currency' => $wallet->currency,
                    'is_active' => $wallet->is_active,
                    'virtual_bank_account' => $wallet->virtualBankAccount ? [
                        'account_number' => $wallet->virtualBankAccount->account_number,
                        'account_name' => $wallet->virtualBankAccount->account_name,
                        'bank_name' => $wallet->virtualBankAccount->bank_name,
                        'provider' => $wallet->virtualBankAccount->provider,
                    ] : null,
                    'transactions_count' => $wallet->transactions->count(),
                    'wallet_transactions_count' => $wallet->walletTransactions->count(),
                    'created_at' => $wallet->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $wallet->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            $wallets->setCollection($formattedWallets);

            return $wallets;
        } catch (Exception $e) {
            Log::error('ADMIN WALLET SERVICE - GET WALLETS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single wallet with detailed information
     */
    public function getWallet(string $id): array
    {
        try {
            $wallet = Wallet::with([
                'user',
                'virtualBankAccount',
                'transactions' => function ($query) {
                    $query->latest()->limit(10);
                },
                'walletTransactions' => function ($query) {
                    $query->latest()->limit(10);
                }
            ])->find($id);

            if (!$wallet) {
                throw new Exception('Wallet not found');
            }

            return [
                'id' => $wallet->id,
                'user' => $wallet->user ? [
                    'id' => $wallet->user->id,
                    'name' => $wallet->user->name,
                    'email' => $wallet->user->email,
                    'username' => $wallet->user->username,
                    'status' => $wallet->user->status,
                ] : null,
                'balance' => $wallet->amount->getAmount()->toFloat(),
                'ledger_balance' => $wallet->ledger_balance->getAmount()->toFloat(),
                'currency' => $wallet->currency,
                'is_active' => $wallet->is_active,
                'virtual_bank_account' => $wallet->virtualBankAccount ? [
                    'id' => $wallet->virtualBankAccount->id,
                    'account_number' => $wallet->virtualBankAccount->account_number,
                    'account_name' => $wallet->virtualBankAccount->account_name,
                    'bank_name' => $wallet->virtualBankAccount->bank_name,
                    'bank_code' => $wallet->virtualBankAccount->bank_code,
                    'provider' => $wallet->virtualBankAccount->provider,
                    'currency' => $wallet->virtualBankAccount->currency,
                    'country' => $wallet->virtualBankAccount->country,
                ] : null,
                'recent_transactions' => $wallet->transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'reference' => $transaction->reference,
                        'type' => $transaction->type,
                        'amount' => $transaction->amount->getAmount()->toFloat(),
                        'status' => $transaction->status,
                        'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'recent_wallet_transactions' => $wallet->walletTransactions->map(function ($wt) {
                    return [
                        'id' => $wt->id,
                        'type' => $wt->type,
                        'previous_balance' => $wt->previous_balance->getAmount()->toFloat(),
                        'new_balance' => $wt->new_balance->getAmount()->toFloat(),
                        'amount_change' => $wt->amount_change->getAmount()->toFloat(),
                        'created_at' => $wt->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'transactions_count' => $wallet->transactions()->count(),
                'wallet_transactions_count' => $wallet->walletTransactions()->count(),
                'created_at' => $wallet->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $wallet->updated_at->format('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            Log::error('ADMIN WALLET SERVICE - GET WALLET: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a wallet
     */
    public function deleteWallet(string $id): void
    {
        try {
            $wallet = Wallet::find($id);

            if (!$wallet) {
                throw new Exception('Wallet not found');
            }

            // Check if wallet has balance
            if ($wallet->amount->getAmount()->toFloat() > 0) {
                throw new Exception('Cannot delete wallet with balance. Please empty wallet first.');
            }

            // Delete associated virtual bank account
            if ($wallet->virtualBankAccount) {
                $wallet->virtualBankAccount->delete();
            }

            // Delete wallet transactions
            $wallet->walletTransactions()->delete();

            // Note: We don't delete regular transactions as they might be needed for audit

            $wallet->delete();
        } catch (Exception $e) {
            Log::error('ADMIN WALLET SERVICE - DELETE WALLET: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }
}
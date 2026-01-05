<?php

namespace App\Modules\Transaction\Services\Admin;

use App\Modules\Commerce\Models\Settlement;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Models\Wallet;
use Exception;
use Illuminate\Support\Facades\Log;

class AdminTransactionService
{
    /**
     * Get all transactions with filters
     */
    public function getTransactions(array $filters = []): mixed
    {
        try {
            $query = Transaction::with('user.vendor')
                ->whereNull('principal_transaction_id') // Only principal transactions
                ->orderBy('created_at', 'desc');

            // Apply search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'LIKE', '%' . $search . '%')
                      ->orWhere('amount', 'LIKE', '%' . $search . '%')
                      ->orWhere('type', 'LIKE', '%' . $search . '%')
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'LIKE', '%' . $search . '%')
                                    ->orWhere('email', 'LIKE', '%' . $search . '%')
                                    ->orWhere('username', 'LIKE', '%' . $search . '%')
                        ->orWhereHas('user.vendor', function ($vendorQuery) use ($search) {
                            $vendorQuery->where('business_name', 'LIKE', '%' . $search . '%');
                        });
                      });
                });
            }

            // Apply status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $query->where('status', strtoupper($filters['status']));
            }

            // Apply type filter
            if (isset($filters['type']) && !empty($filters['type'])) { 
                $query->where('type', strtoupper($filters['type']));
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
            $transactions = $query->paginate($perPage);

            $formattedTransactions = $transactions->getCollection()->map(function ($transaction) {
                return [
                    'transactionId' => $transaction->reference,
                    'user' => $transaction->user->vendor ? $transaction->user->vendor->business_name : $transaction->user->name,
                    'amount' => $transaction->amount->getAmount()->toFloat(),
                    'type' => $transaction->type,
                    'status' => $transaction->status,
                    'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $transactions->setCollection($formattedTransactions);

            return $transactions;
        } catch (Exception $e) {
            Log::error('ADMIN TRANSACTION SERVICE - GET TRANSACTIONS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single transaction with detailed information
     */
    public function getTransaction(string $id): array
    {
        try {
            $transaction = Transaction::with(['user', 'wallet', 'feeTransactions'])
                ->where('reference', $id)
                ->whereNull('principal_transaction_id')
                ->first();

            if (!$transaction) {
                throw new Exception('Transaction not found');
            }

            $fee = $transaction->feeTransactions->sum(function ($t) {
                return $t->amount->getAmount()->toFloat();
            });

            $payload = is_array($transaction->payload) ? $transaction->payload : (array) json_decode(json_encode($transaction->payload), true);

            $sender = [
                'name' => $transaction->user ? $transaction->user->name : null,
                'email' => $transaction->user ? $transaction->user->email : null,
                'username' => $transaction->user ? $transaction->user->username : null,
            ];

            $recipient = null;

            // Payment / transfer style recipient
            if (in_array($transaction->type, ['SEND_MONEY'])) {
                $recipient = [
                    'account_number' => $payload['account_number'] ?? null,
                    'bank_code' => $payload['bank_code'] ?? null,
                    'bank_name' => $payload['bank_name'] ?? null,
                    'account_name' => $payload['account_name'] ?? null,
                ];
            }

            // Fee transaction flag
            $isFee = str_ends_with($transaction->type, '_FEE');

            // Compute total debited (only for outward debit types, exclude FUND_WALLET)
            $debitBaseTypes = ['SEND_MONEY', 'ORDER_PAYMENT'];
            $totalDebited = in_array($transaction->type, $debitBaseTypes)
                ? $transaction->amount->getAmount()->toFloat() + $fee
                : $transaction->amount->getAmount()->toFloat();

            return [
                'reference' => $transaction->reference,
                'external_reference' => $transaction->external_transaction_reference,
                'type' => $transaction->type,
                'is_fee' => $isFee,
                'status' => $transaction->status,
                'description' => $transaction->description,
                'narration' => $transaction->narration,
                'amount' => $transaction->amount->getAmount()->toFloat(),
                'currency' => $transaction->currency,
                'fee' => $fee,
                'total_debited' => $totalDebited,
                'date' => $transaction->created_at->toDateTimeString(),
                'date_human' => $transaction->created_at->format('F j, Y g:i:s A'),
                'user_ip' => $transaction->user_ip,
                'sender' => $sender,
                'recipient' => $recipient,
                'payload' => $payload,
            ];
        } catch (Exception $e) {
            Log::error('ADMIN TRANSACTION SERVICE - GET TRANSACTION: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get transaction statistics
     */
    public function getTransactionStats(): array
    {
        try {
            // 1. Total withdrawal: Sum of successful SEND_MONEY transactions
            $withdrawalTransactions = Transaction::where('type', 'SEND_MONEY')
                ->where('status', 'SUCCESSFUL')
                ->get();
            
            $totalWithdrawal = $withdrawalTransactions->sum(function ($transaction) {
                return $transaction->amount->getAmount()->toFloat();
            });

            // 2. Total settlement: Sum of vendor_amount from successful settlements
            $successfulSettlements = Settlement::where('status', 'SUCCESSFUL')->get();
            
            $totalSettlement = $successfulSettlements->sum(function ($settlement) {
                return $settlement->vendor_amount->getAmount()->toFloat();
            });

            // 3. Total balance volume: Sum of all wallet balances
            $wallets = Wallet::all();
            
            $totalBalanceVolume = $wallets->sum(function ($wallet) {
                return $wallet->amount->getAmount()->toFloat();
            });

            return [
                'total_withdrawal' => $totalWithdrawal,
                'total_settlement' => $totalSettlement,
                'total_balance_volume' => $totalBalanceVolume,
            ];
        } catch (Exception $e) {
            Log::error('ADMIN TRANSACTION SERVICE - GET STATS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }
}
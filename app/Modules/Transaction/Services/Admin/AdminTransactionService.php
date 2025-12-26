<?php

namespace App\Services\Admin;

use App\Models\Transaction;
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
            $query = Transaction::with('user')
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
                                    ->orWhere('username', 'LIKE', '%' . $search . '%');
                      });
                });
            }

            // Apply status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $query->where('status', strtoupper($filters['status']));
            }

            // Apply types filter
            if (isset($filters['types']) && !empty($filters['types'])) {
                $types = explode(',', $filters['types']);
                $mappedTypes = [];

                foreach ($types as $type) {
                    switch (strtolower($type)) {
                        case 'payment':
                            $mappedTypes[] = 'FUND_WALLET';
                            break;
                        case 'transfers':
                            $mappedTypes[] = 'SEND_MONEY';
                            break;
                        case 'withdrawals':
                            // Assuming withdrawals might be SEND_MONEY or other types
                            $mappedTypes[] = 'SEND_MONEY';
                            break;
                    }
                }

                if (!empty($mappedTypes)) {
                    $query->whereIn('type', $mappedTypes);
                }
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
                    'user' => $transaction->user ? $transaction->user->name : null,
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
            $transaction = Transaction::with(['user', 'wallet.virtualBankAccount', 'feeTransactions'])
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
                'account_number' => $transaction->wallet && $transaction->wallet->virtualBankAccount ? $transaction->wallet->virtualBankAccount->account_number : null,
                'bank_name' => $transaction->wallet && $transaction->wallet->virtualBankAccount ? $transaction->wallet->virtualBankAccount->bank_name : null,
            ];

            $recipient = null;
            $service = null;

            // Payment / transfer style recipient
            if (in_array($transaction->type, ['SEND_MONEY'])) {
                $recipient = [
                    'name' => $payload['name'] ?? null,
                    'username' => $payload['username'] ?? null,
                    'email' => $payload['email'] ?? null,
                    'account_number' => $payload['account_number'] ?? null,
                    'bank_code' => $payload['bank_code'] ?? null,
                    'bank_name' => $payload['bank_name'] ?? null,
                    'account_name' => $payload['account_name'] ?? null,
                    'type' => $payload['type'] ?? null,
                ];
            } elseif ($transaction->type === 'REQUEST_MONEY') {
                $recipient = [
                    'requested_from_name' => $payload['name'] ?? null,
                    'requested_from_username' => $payload['username'] ?? null,
                    'requested_from_email' => $payload['email'] ?? null,
                    'status' => $payload['status'] ?? null,
                    'type' => $payload['type'] ?? null,
                ];
            }

            // Utilities / services (AIRTIME, DATA, CABLETV, UTILITY)
            $serviceTypes = ['AIRTIME','DATA','CABLETV','UTILITY'];
            if (in_array($transaction->type, $serviceTypes)) {
                // Whitelist likely payload keys for service display
                $possibleKeys = [
                    'phone_number','network','vendType', 'token', 'units', 'validity',
                    'package','package_name','id','iuc_number',
                    'number','customer_name','address','company','provider',
                    'product_code','service_type','biller_code','account_number',
                ];
                $extracted = [];
                foreach ($possibleKeys as $k) {
                    if (array_key_exists($k, $payload)) {
                        $extracted[$k] = $payload[$k];
                    }
                }
                $service = [
                    'category' => $transaction->type,
                    'details' => $extracted,
                ];
            }

            // Fee transaction flag
            $isFee = str_ends_with($transaction->type, '_FEE');

            // Compute total debited (only for outward debit types, exclude FUND_WALLET)
            $debitBaseTypes = array_merge(['SEND_MONEY','SUBSCRIPTION','TRANSACTION_SYNC'], $serviceTypes);
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
                'service' => $service,
                'payload' => $payload,
            ];
        } catch (Exception $e) {
            Log::error('ADMIN TRANSACTION SERVICE - GET TRANSACTION: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }
}
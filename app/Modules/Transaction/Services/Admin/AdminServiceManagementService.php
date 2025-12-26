<?php

namespace App\Services\Admin;

use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\Log;

class AdminServiceManagementService
{
    /**
     * Get airtime service statistics
     */
    public function getAirtimeStatistics(array $filters = []): array
    {
        try {
            $query = Transaction::where('type', 'AIRTIME')
                ->with(['feeTransactions'])
                ->whereNull('principal_transaction_id');

            // Apply date range filters if provided
            if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date']) && !empty($filters['end_date'])) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            }

            // Get total purchases count
            $totalPurchases = (clone $query)
                ->count();

            $totalPending = (clone $query)
                ->where('status', 'PENDING')
                ->count();

            // Get total revenue (amount + fees)
            $totalRevenue = (clone $query)
                ->where('status', 'SUCCESSFUL')
                ->get()
                ->sum(function ($transaction) {
                    $amount = $transaction->amount->getAmount()->toFloat();
                    $fees = $transaction->feeTransactions->sum(function ($feeTransaction) {
                        return $feeTransaction->amount->getAmount()->toFloat();
                    });
                    return $amount + $fees;
                });

            // Get success rate
            $successfulCount = (clone $query)->where('status', 'SUCCESSFUL')->count();
            $successRate = $totalPurchases > 0 ? round(($successfulCount / $totalPurchases) * 100, 2) : 0;

            return [
                'totalPurchases' => $totalPurchases,
                'totalPending' => $totalPending,
                'totalRevenue' => $totalRevenue,
                'successRate' => $successRate,
            ];
        } catch (Exception $e) {
            Log::error('ADMIN SERVICE MANAGEMENT SERVICE - AIRTIME STATISTICS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get airtime transactions with filters
     */
    public function getAirtimeTransactions(array $filters = []): mixed
    {
        try {
            $query = Transaction::with(['user', 'feeTransactions'])
                ->where('type', 'AIRTIME')
                ->whereNull('principal_transaction_id')
                ->orderBy('created_at', 'desc');

            // Apply search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'LIKE', '%' . $search . '%')
                      ->orWhere('payload->phone_number', 'LIKE', '%' . $search . '%')
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'LIKE', '%' . $search . '%')
                                    ->orWhere('email', 'LIKE', '%' . $search . '%')
                                    ->orWhere('username', 'LIKE', '%' . $search . '%');
                      });
                });
            }

            // Apply network filter
            if (isset($filters['networks']) && !empty($filters['networks'])) {
                $networks = explode(',', $filters['networks']);
                $query->where(function ($q) use ($networks) {
                    foreach ($networks as $network) {
                        $q->orWhere('payload->network', $network);
                    }
                });
            }

            // Apply status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $query->where('status', strtoupper($filters['status']));
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
                    'phoneNumber' => $transaction->payload['phone_number'] ?? null,
                    'network' => $transaction->payload['network'] ?? null,
                    'status' => $transaction->status,
                    'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $transactions->setCollection($formattedTransactions);

            return $transactions;
        } catch (Exception $e) {
            Log::error('ADMIN SERVICE MANAGEMENT SERVICE - AIRTIME INDEX: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single airtime transaction with detailed information
     */
    public function getAirtimeTransaction(string $id): array
    {
        try {
            $transaction = Transaction::with(['user', 'wallet.virtualBankAccount', 'feeTransactions'])
                ->where('reference', $id)
                ->where('type', 'AIRTIME')
                ->whereNull('principal_transaction_id')
                ->first();

            if (!$transaction) {
                throw new Exception('Airtime transaction not found');
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

            $service = [
                'category' => 'AIRTIME',
                'details' => [
                    'phone_number' => $payload['phone_number'] ?? null,
                    'network' => $payload['network'] ?? null,
                ],
            ];

            $totalDebited = $transaction->amount->getAmount()->toFloat() + $fee;

            return [
                'reference' => $transaction->reference,
                'external_reference' => $transaction->external_transaction_reference,
                'type' => $transaction->type,
                'is_fee' => false,
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
                'service' => $service,
                'payload' => $payload,
            ];
        } catch (Exception $e) {
            Log::error('ADMIN SERVICE MANAGEMENT SERVICE - GET AIRTIME TRANSACTION: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all airtime networks
     */
    public function getAirtimeNetworks(): array
    {
        try {
            $airtimeService = resolve(\App\Services\Utilities\AirtimeService::class);
            return $airtimeService->getNetworks();
        } catch (Exception $e) {
            Log::error('ADMIN SERVICE MANAGEMENT SERVICE - GET AIRTIME NETWORKS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get data service statistics
     */
    public function getDataStatistics(array $filters = []): array
    {
        try {
            $query = Transaction::where('type', 'DATA')
                ->with(['feeTransactions'])
                ->whereNull('principal_transaction_id');

            // Apply date range filters if provided
            if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date']) && !empty($filters['end_date'])) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            }

            // Get total purchases count
             // Get total purchases count
            $totalPurchases = (clone $query)
                ->count();

            $totalPending = (clone $query)
                ->where('status', 'PENDING')
                ->count();

            // Get total revenue (amount + fees)
            $totalRevenue = (clone $query)
                ->where('status', 'SUCCESSFUL')
                ->get()
                ->sum(function ($transaction) {
                    $amount = $transaction->amount->getAmount()->toFloat();
                    $fees = $transaction->feeTransactions->sum(function ($feeTransaction) {
                        return $feeTransaction->amount->getAmount()->toFloat();
                    });
                    return $amount + $fees;
                });

            // Get success rate
            $successfulCount = (clone $query)->where('status', 'SUCCESSFUL')->count();
            $successRate = $totalPurchases > 0 ? round(($successfulCount / $totalPurchases) * 100, 2) : 0;

            // Get active plans (count of distinct plans)
            $activePlans = (clone $query)
                ->where('status', 'SUCCESSFUL')
                ->whereNotNull('payload->plan')
                ->distinct()
                ->count('payload->plan');

            return [
                'totalPurchases' => $totalPurchases,
                'totalPending' => $totalPending,
                'totalRevenue' => $totalRevenue,
                'successRate' => $successRate,
                'activePlans' => $activePlans,
            ];
        } catch (Exception $e) {
            Log::error('ADMIN SERVICE MANAGEMENT SERVICE - DATA STATISTICS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get data transactions with filters
     */
    public function getDataTransactions(array $filters = []): mixed
    {
        try {
            $query = Transaction::with(['user', 'feeTransactions'])
                ->where('type', 'DATA')
                ->whereNull('principal_transaction_id')
                ->orderBy('created_at', 'desc');

            // Apply search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'LIKE', '%' . $search . '%')
                      ->orWhere('payload->phone_number', 'LIKE', '%' . $search . '%')
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'LIKE', '%' . $search . '%')
                                    ->orWhere('email', 'LIKE', '%' . $search . '%')
                                    ->orWhere('username', 'LIKE', '%' . $search . '%');
                      });
                });
            }

            // Apply network filter
            if (isset($filters['networks']) && !empty($filters['networks'])) {
                $networks = explode(',', $filters['networks']);
                $query->where(function ($q) use ($networks) {
                    foreach ($networks as $network) {
                        $q->orWhere('payload->network', $network);
                    }
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
            $transactions = $query->paginate($perPage);

            $formattedTransactions = $transactions->getCollection()->map(function ($transaction) {
                return [
                    'transactionId' => $transaction->reference,
                    'user' => $transaction->user ? $transaction->user->name : null,
                    'phoneNumber' => $transaction->payload['phone_number'] ?? null,
                    'network' => $transaction->payload['network'] ?? null,
                    'plan' => $transaction->payload['plan'] ?? null,
                    'amount' => $transaction->amount->getAmount()->toFloat(),
                    'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $transactions->setCollection($formattedTransactions);

            return $transactions;
        } catch (Exception $e) {
            Log::error('ADMIN SERVICE MANAGEMENT SERVICE - DATA INDEX: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single data transaction with detailed information
     */
    public function getDataTransaction(string $id): array
    {
        try {
            $transaction = Transaction::with(['user', 'wallet.virtualBankAccount', 'feeTransactions'])
                ->where('reference', $id)
                ->where('type', 'DATA')
                ->whereNull('principal_transaction_id')
                ->first();

            if (!$transaction) {
                throw new Exception('Data transaction not found');
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

            $service = [
                'category' => 'DATA',
                'details' => [
                    'phone_number' => $payload['phone_number'] ?? null,
                    'network' => $payload['network'] ?? null,
                    'plan' => $payload['plan'] ?? null,
                ],
            ];

            $totalDebited = $transaction->amount->getAmount()->toFloat() + $fee;

            return [
                'reference' => $transaction->reference,
                'external_reference' => $transaction->external_transaction_reference,
                'type' => $transaction->type,
                'is_fee' => false,
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
                'service' => $service,
                'payload' => $payload,
            ];
        } catch (Exception $e) {
            Log::error('ADMIN SERVICE MANAGEMENT SERVICE - GET DATA TRANSACTION: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get electricity service statistics
     */
    public function getElectricityStatistics(array $filters = []): array
    {
        try {
            $query = Transaction::where('type', 'UTILITY')
                ->with(['feeTransactions'])
                ->whereNull('principal_transaction_id');

            // Apply date range filters if provided
            if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date']) && !empty($filters['end_date'])) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            }

            // Get total payments count
            $totalPayments = (clone $query)
                ->count();

            $totalPending = (clone $query)
                ->where('status', 'PENDING')
                ->count();

            // Get total revenue (amount + fees)
            $totalRevenue = (clone $query)
                ->where('status', 'SUCCESSFUL')
                ->get()
                ->sum(function ($transaction) {
                    $amount = $transaction->amount->getAmount()->toFloat();
                    $fees = $transaction->feeTransactions->sum(function ($feeTransaction) {
                        return $feeTransaction->amount->getAmount()->toFloat();
                    });
                    return $amount + $fees;
                });

            // Get success rate
            $successfulCount = (clone $query)->where('status', 'SUCCESSFUL')->count();
            $successRate = $totalPayments > 0 ? round(($successfulCount / $totalPayments) * 100, 2) : 0;

            // Get providers count (distinct companies)
            $providers = (clone $query)
                ->where('status', 'SUCCESSFUL')
                ->whereNotNull('payload->company')
                ->distinct()
                ->count('payload->company');

            return [
                'totalPayments' => $totalPayments,
                'totalPending' => $totalPending,
                'totalRevenue' => $totalRevenue,
                'successRate' => $successRate,
                'providers' => $providers,
            ];
        } catch (Exception $e) {
            Log::error('ADMIN SERVICE MANAGEMENT SERVICE - ELECTRICITY STATISTICS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get electricity transactions with filters
     */
    public function getElectricityTransactions(array $filters = []): mixed
    {
        try {
            $query = Transaction::with(['user', 'feeTransactions'])
                ->where('type', 'UTILITY')
                ->whereNull('principal_transaction_id')
                ->orderBy('created_at', 'desc');

            // Apply search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'LIKE', '%' . $search . '%')
                      ->orWhere('payload->number', 'LIKE', '%' . $search . '%')
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'LIKE', '%' . $search . '%')
                                    ->orWhere('email', 'LIKE', '%' . $search . '%')
                                    ->orWhere('username', 'LIKE', '%' . $search . '%');
                      });
                });
            }

            // Apply providers filter
            if (isset($filters['providers']) && !empty($filters['providers'])) {
                $providers = explode(',', $filters['providers']);
                $query->where(function ($q) use ($providers) {
                    foreach ($providers as $provider) {
                        $q->orWhere('payload->company', $provider);
                    }
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
            $transactions = $query->paginate($perPage);

            $formattedTransactions = $transactions->getCollection()->map(function ($transaction) {
                return [
                    'transactionId' => $transaction->reference,
                    'user' => $transaction->user ? $transaction->user->name : null,
                    'meterNumber' => $transaction->payload['number'] ?? null,
                    'provider' => $transaction->payload['company'] ?? null,
                    'amount' => $transaction->amount->getAmount()->toFloat(),
                    'units' => $transaction->payload['units'] ?? null,
                    'status' => $transaction->status,
                    'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $transactions->setCollection($formattedTransactions);

            return $transactions;
        } catch (Exception $e) {
            Log::error('ADMIN SERVICE MANAGEMENT SERVICE - ELECTRICITY INDEX: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single electricity transaction with detailed information
     */
    public function getElectricityTransaction(string $id): array
    {
        try {
            $transaction = Transaction::with(['user', 'wallet.virtualBankAccount', 'feeTransactions'])
                ->where('reference', $id)
                ->where('type', 'UTILITY')
                ->whereNull('principal_transaction_id')
                ->first();

            if (!$transaction) {
                throw new Exception('Electricity transaction not found');
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

            $service = [
                'category' => 'UTILITY',
                'details' => [
                    'number' => $payload['number'] ?? null,
                    'company' => $payload['company'] ?? null,
                    'units' => $payload['units'] ?? null,
                    'token' => $payload['token'] ?? null,
                    'vendType' => $payload['vendType'] ?? null,
                    'vendTime' => $payload['vendTime'] ?? null,
                ],
            ];

            $totalDebited = $transaction->amount->getAmount()->toFloat() + $fee;

            return [
                'reference' => $transaction->reference,
                'external_reference' => $transaction->external_transaction_reference,
                'type' => $transaction->type,
                'is_fee' => false,
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
                'service' => $service,
                'payload' => $payload,
            ];
        } catch (Exception $e) {
            Log::error('ADMIN SERVICE MANAGEMENT SERVICE - GET ELECTRICITY TRANSACTION: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get TV service statistics
     */
    public function getTVStatistics(array $filters = []): array
    {
        try {
            $query = Transaction::where('type', 'CABLETV')
                ->with(['feeTransactions'])
                ->whereNull('principal_transaction_id');

            // Apply date range filters if provided
            if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date']) && !empty($filters['end_date'])) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            }

            // Get total subscriptions count
            $totalSubscriptions = (clone $query)
                ->count();

            $totalPending = (clone $query)
                ->where('status', 'PENDING')
                ->count();

            // Get total revenue (amount + fees)
            $totalRevenue = (clone $query)
                ->where('status', 'SUCCESSFUL')
                ->get()
                ->sum(function ($transaction) {
                    $amount = $transaction->amount->getAmount()->toFloat();
                    $fees = $transaction->feeTransactions->sum(function ($feeTransaction) {
                        return $feeTransaction->amount->getAmount()->toFloat();
                    });
                    return $amount + $fees;
                });

            // Get success rate
            $successfulCount = (clone $query)->where('status', 'SUCCESSFUL')->count();
            $successRate = $totalSubscriptions > 0 ? round(($successfulCount / $totalSubscriptions) * 100, 2) : 0;

            // Get active providers (distinct companies)
            $activeProviders = (clone $query)
                ->where('status', 'SUCCESSFUL')
                ->whereNotNull('payload->company')
                ->distinct()
                ->count('payload->company');

            return [
                'totalSubscriptions' => $totalSubscriptions,
                'totalPending' => $totalPending,
                'totalRevenue' => $totalRevenue,
                'successRate' => $successRate,
                'activeProviders' => $activeProviders,
            ];
        } catch (Exception $e) {
            Log::error('ADMIN SERVICE MANAGEMENT SERVICE - TV STATISTICS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get TV transactions with filters
     */
    public function getTVTransactions(array $filters = []): mixed
    {
        try {
            $query = Transaction::with(['user', 'feeTransactions'])
                ->where('type', 'CABLETV')
                ->whereNull('principal_transaction_id')
                ->orderBy('created_at', 'desc');

            // Apply search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'LIKE', '%' . $search . '%')
                      ->orWhere('payload->number', 'LIKE', '%' . $search . '%')
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'LIKE', '%' . $search . '%')
                                    ->orWhere('email', 'LIKE', '%' . $search . '%')
                                    ->orWhere('username', 'LIKE', '%' . $search . '%');
                      });
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
            $transactions = $query->paginate($perPage);

            $formattedTransactions = $transactions->getCollection()->map(function ($transaction) {
                return [
                    'transactionId' => $transaction->reference,
                    'user' => $transaction->user ? $transaction->user->name : null,
                    'smartcardNumber' => $transaction->payload['number'] ?? null,
                    'provider' => $transaction->payload['company'] ?? null,
                    'package' => $transaction->payload['package'] ?? null,
                    'amount' => $transaction->amount->getAmount()->toFloat(),
                    'status' => $transaction->status,
                    'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $transactions->setCollection($formattedTransactions);

            return $transactions;
        } catch (Exception $e) {
            Log::error('ADMIN SERVICE MANAGEMENT SERVICE - TV INDEX: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single TV transaction with detailed information
     */
    public function getTVTransaction(string $id): array
    {
        try {
            $transaction = Transaction::with(['user', 'wallet.virtualBankAccount', 'feeTransactions'])
                ->where('reference', $id)
                ->where('type', 'CABLETV')
                ->whereNull('principal_transaction_id')
                ->first();

            if (!$transaction) {
                throw new Exception('TV transaction not found');
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

            $service = [
                'category' => 'CABLETV',
                'details' => [
                    'number' => $payload['number'] ?? null,
                    'company' => $payload['company'] ?? null,
                    'package' => $payload['package'] ?? null,
                    'name' => $payload['name'] ?? null,
                ],
            ];

            $totalDebited = $transaction->amount->getAmount()->toFloat() + $fee;

            return [
                'reference' => $transaction->reference,
                'external_reference' => $transaction->external_transaction_reference,
                'type' => $transaction->type,
                'is_fee' => false,
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
                'service' => $service,
                'payload' => $payload,
            ];
        } catch (Exception $e) {
            Log::error('ADMIN SERVICE MANAGEMENT SERVICE - GET TV TRANSACTION: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all TV providers
     */
    public function getTVProviders(): array
    {
        try {
            $cableTVService = resolve(\App\Services\Utilities\CableTVService::class);
            return $cableTVService->getCompanies();
        } catch (Exception $e) {
            Log::error('ADMIN SERVICE MANAGEMENT SERVICE - GET TV PROVIDERS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all electricity providers
     */
    public function getElectricityProviders(): array
    {
        try {
            $utilityService = resolve(\App\Services\Utilities\UtilityService::class);
            return $utilityService->getCompanies();
        } catch (Exception $e) {
            Log::error('ADMIN SERVICE MANAGEMENT SERVICE - GET ELECTRICITY PROVIDERS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all data service providers
     */
    public function getDataProviders(): array
    {
        try {
            $dataService = resolve(\App\Services\Utilities\DataService::class);
            return $dataService->getNetworks();
        } catch (Exception $e) {
            Log::error('ADMIN SERVICE MANAGEMENT SERVICE - GET DATA PROVIDERS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }
}
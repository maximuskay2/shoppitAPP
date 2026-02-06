<?php

namespace App\Modules\Transaction\Services\Admin;

use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Settlement;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Models\Wallet;
use App\Modules\User\Models\User;
use Brick\Money\Money;
use Exception;
use Illuminate\Support\Facades\DB;
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

    /**
     * Get transaction reports
     */
    public function getTransactionReports($request): array
    {
        try {
            // Scope: if start_date/end_date provided, use them; otherwise all-time
            $hasRange = $request && ($request->has('start_date') || $request->has('end_date'));
            $startDate = $request->has('start_date') ? \Carbon\Carbon::parse($request->input('start_date'))->startOfDay() : null;
            $endDate = $request->has('end_date') ? \Carbon\Carbon::parse($request->input('end_date'))->endOfDay() : null;

            // 1) Total revenue: sum of platform_fee from successful settlements (scoped by settled_at if provided)
            $settlementQuery = Settlement::where('status', 'SUCCESSFUL');
            if ($hasRange) {
                $settlementQuery->whereBetween('settled_at', [$startDate ?? '1970-01-01', $endDate ?? now()]);
            }
            $totalRevenue = $settlementQuery->get()->sum(function ($settlement) {
                return $settlement->platform_fee->getAmount()->toFloat();
            });

            // 2) Orders completed count (scoped by created_at if range provided)
            $ordersQuery = Order::query();
            if ($hasRange) {
                $ordersQuery->whereBetween('created_at', [$startDate ?? '1970-01-01', $endDate ?? now()]);
            }
            $totalCompletedOrders = (clone $ordersQuery)->whereIn('status', ['COMPLETED', 'DELIVERED'])->count();

            // 3) Refunded or cancelled orders
            $totalRefundedOrCancelled = (clone $ordersQuery)->whereIn('status', ['CANCELLED', 'REFUNDED'])->count();

            // 4) New users creation
            $usersQuery = User::query();
            if ($hasRange) {
                $usersQuery->whereBetween('created_at', [$startDate ?? '1970-01-01', $endDate ?? now()]);
            }
            $newUsers = $usersQuery->count();

            // Chart 1: User growth by month for a given year (use 'year' param or current year)
            $year = $request->has('year') ? intval($request->input('year')) : now()->year;
            $usersForYear = User::whereYear('created_at', $year)->get(['created_at']);

            $userMonthly = array_fill(1, 12, 0);
            foreach ($usersForYear as $u) {
                $m = intval($u->created_at->format('n'));
                $userMonthly[$m] = ($userMonthly[$m] ?? 0) + 1;
            }

            $userGrowth = [];
            for ($m = 1; $m <= 12; $m++) {
                $userGrowth[] = [
                    'month' => $m,
                    'count' => $userMonthly[$m] ?? 0,
                ];
            }

            // Chart 2: Order breakdown by status within scoped range (include zeros)
            $allStatuses = [
                'PENDING',
                'PAID',
                'PROCESSING',
                'READY_FOR_PICKUP',
                'PICKED_UP',
                'OUT_FOR_DELIVERY',
                'DISPATCHED',
                'DELIVERED',
                'COMPLETED',
                'CANCELLED',
                'REFUNDED',
            ];
            $orderBreakdownQuery = Order::query();
            if ($hasRange) {
                $orderBreakdownQuery->whereBetween('created_at', [$startDate ?? '1970-01-01', $endDate ?? now()]);
            }
            $ordersForBreakdown = $orderBreakdownQuery->get(['status']);
            $orderTotalForPct = $ordersForBreakdown->count();

            $statusCounts = [];
            foreach ($ordersForBreakdown as $o) {
                $s = $o->status;
                $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
            }

            $orderBreakdown = [];
            foreach ($allStatuses as $status) {
                $count = $statusCounts[$status] ?? 0;
                $pct = $orderTotalForPct > 0 ? round(($count / $orderTotalForPct) * 100, 2) : 0;
                $orderBreakdown[] = [
                    'status' => $status,
                    'count' => $count,
                    'percentage' => $pct,
                ];
            }

            // Chart 3: Sales overview - monthly successful orders sums for a given year
            $salesYear = $request->has('year') ? intval($request->input('year')) : $year;
            // Chart 3: Sales overview - monthly successful orders sums for a given year
            $ordersForSales = Order::whereIn('status', ['COMPLETED', 'DELIVERED'])
                ->whereYear('created_at', $salesYear)
                ->get(['created_at', 'gross_total_amount']);

            $salesByMonth = array_fill(1, 12, 0.0);
            foreach ($ordersForSales as $o) {
                $m = intval($o->created_at->format('n'));
                $amount = 0.0;
                if ($o->gross_total_amount instanceof Money) {
                    $amount = $o->gross_total_amount->getAmount()->toFloat();
                } else {
                    // Fallback if cast not applied yet
                    $amount = floatval($o->gross_total_amount);
                }
                $salesByMonth[$m] = ($salesByMonth[$m] ?? 0.0) + $amount;
            }

            $monthlySales = [];
            for ($m = 1; $m <= 12; $m++) {
                $monthlySales[] = [
                    'month' => $m,
                    'total' => $salesByMonth[$m] ?? 0.0,
                ];
            }

            return [
                'total_revenue' => $totalRevenue,
                'orders_completed' => $totalCompletedOrders,
                'orders_refunded_or_cancelled' => $totalRefundedOrCancelled,
                'new_users' => $newUsers,
                'charts' => [
                    'user_growth_by_month' => $userGrowth,
                    'order_status_breakdown' => $orderBreakdown,
                    'monthly_sales_overview' => $monthlySales,
                ],
            ];
        } catch (Exception $e) {
            Log::error('ADMIN TRANSACTION SERVICE - GET REPORTS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }
}
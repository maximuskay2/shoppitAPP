<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WalletController extends Controller
{
    /**
     * Get wallet balance
     */
    public function balance(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            return ShopittPlus::response(true, 'Wallet balance retrieved successfully', 200, [
                'balance' => $user->balance,
                'currency' => 'NGN',
                'formatted_balance' => '₦' . number_format($user->balance / 100, 2),
            ]);
        } catch (\Exception $e) {
            Log::error('GET WALLET BALANCE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve wallet balance', 500);
        }
    }

    /**
     * Get wallet transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 20);

            $transactions = $user->transactions()
                ->with('wallet')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return ShopittPlus::response(true, 'Wallet transactions retrieved successfully', 200, $transactions);
        } catch (\Exception $e) {
            Log::error('GET WALLET TRANSACTIONS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve wallet transactions', 500);
        }
    }

    /**
     * Deposit funds to wallet
     */
    public function deposit(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:100', // Minimum 100 kobo (₦1)
                'description' => 'nullable|string|max:255',
            ]);

            $user = Auth::user();
            $amount = (int) ($request->amount * 100); // Convert to kobo

            $transaction = $user->deposit($amount, [
                'description' => $request->description ?? 'Wallet deposit',
                'type' => 'deposit',
            ]);

            return ShopittPlus::response(true, 'Deposit successful', 200, [
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'balance' => $user->balance,
            ]);
        } catch (InvalidArgumentException $e) {
            Log::error('WALLET DEPOSIT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('WALLET DEPOSIT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Deposit failed', 500);
        }
    }

    /**
     * Withdraw funds from wallet
     */
    public function withdraw(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:100', // Minimum 100 kobo (₦1)
                'description' => 'nullable|string|max:255',
            ]);

            $user = Auth::user();
            $amount = (int) ($request->amount * 100); // Convert to kobo

            if ($user->balance < $amount) {
                return ShopittPlus::response(false, 'Insufficient wallet balance', 400);
            }

            $transaction = $user->withdraw($amount, [
                'description' => $request->description ?? 'Wallet withdrawal',
                'type' => 'withdrawal',
            ]);

            return ShopittPlus::response(true, 'Withdrawal successful', 200, [
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'balance' => $user->balance,
            ]);
        } catch (InvalidArgumentException $e) {
            Log::error('WALLET WITHDRAWAL: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('WALLET WITHDRAWAL: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Withdrawal failed', 500);
        }
    }

    /**
     * Transfer funds between users
     */
    public function transfer(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'recipient_id' => 'required|exists:users,id',
                'amount' => 'required|numeric|min:100',
                'description' => 'nullable|string|max:255',
            ]);

            $user = Auth::user();
            $recipientId = $request->recipient_id;
            $amount = (int) ($request->amount * 100);

            if ($user->id === $recipientId) {
                return ShopittPlus::response(false, 'Cannot transfer to yourself', 400);
            }

            if ($user->balance < $amount) {
                return ShopittPlus::response(false, 'Insufficient wallet balance', 400);
            }

            $recipient = \App\Modules\User\Models\User::find($recipientId);

            $transfer = $user->transfer($recipient, $amount, [
                'description' => $request->description ?? 'Wallet transfer',
                'type' => 'transfer',
            ]);

            return ShopittPlus::response(true, 'Transfer successful', 200, [
                'transfer_id' => $transfer->id,
                'amount' => $amount,
                'recipient' => $recipient->name,
                'balance' => $user->balance,
            ]);
        } catch (InvalidArgumentException $e) {
            Log::error('WALLET TRANSFER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('WALLET TRANSFER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Transfer failed', 500);
        }
    }

    /**
     * Get wallet dashboard data
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Get wallet transactions (Laravel-Wallet transactions)
            $walletTransactions = $user->transactions()
                ->with('wallet')
                ->orderBy('created_at', 'desc')
                ->get();

            // Calculate balances
            $availableBalance = $user->balance; // Confirmed balance from Laravel-Wallet

            // For pending balance, we consider unconfirmed transactions
            $pendingBalance = $walletTransactions
                ->where('confirmed', false)
                ->where('type', 'deposit')
                ->sum('amount');

            // Calculate total earnings (all positive transactions)
            $totalEarnings = $walletTransactions
                ->where('type', 'deposit')
                ->where('confirmed', true)
                ->sum('amount');

            // Format recent transactions
            $recentTransactions = $walletTransactions
                ->take(10)
                ->map(function ($transaction) {
                    // Map Laravel-Wallet transaction types to dashboard types
                    $type = match($transaction->type) {
                        'deposit' => 'sale', // Assuming deposits are from sales
                        'withdraw' => 'withdrawal',
                        default => 'commission' // Default to commission for other types
                    };

                    // Generate transaction ID
                    $transactionId = 'TXN-' . strtoupper(substr($transaction->uuid, 0, 8));

                    return [
                        'id' => $transactionId,
                        'type' => $type,
                        'amount' => (int) ($transaction->amount / 100), // Convert from kobo to naira
                        'status' => $transaction->confirmed ? 'completed' : 'pending',
                        'date' => $transaction->created_at->format('Y-m-d'),
                        'description' => $transaction->meta['description'] ?? ucfirst($type),
                    ];
                })
                ->values()
                ->toArray();

          
            $dashboardData = [
                'available_balance' => (int) ($availableBalance / 100), // Convert from kobo
                'pending_balance' => (int) ($pendingBalance / 100), // Convert from kobo
                'total_earnings' => (int) ($totalEarnings / 100), // Convert from kobo
                'transactions' => $recentTransactions,
            ];

            return ShopittPlus::response(true, 'Wallet dashboard data retrieved successfully', 200, $dashboardData);
        } catch (\Exception $e) {
            Log::error('WALLET DASHBOARD: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve wallet dashboard data', 500);
        }
    }
}

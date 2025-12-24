<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Transaction\Services\WalletService;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}
    /**
     * Get wallet balance
     */
    public function balance(Request $request): JsonResponse
    {
        try {
            $user = User::find(Auth::id());
            $wallet = $user->wallet;

            return ShopittPlus::response(true, 'Wallet balance retrieved successfully', 200, [
                'balance' => $wallet->balance,
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
                'amount' => 'required|numeric|min:100', 
            ]);
            $user = User::find(Auth::id());
            
            $response = $this->walletService->addFunds($user, $request->amount, $request->ip());
            return ShopittPlus::response(true, 'Deposit processed', 200, $response);
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
}

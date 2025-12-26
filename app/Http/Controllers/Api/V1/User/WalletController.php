<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vendor\WithdrawalRequest;
use App\Modules\Transaction\Services\TransactionService;
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

            $balance = $this->walletService->getBalance($user);
            return ShopittPlus::response(true, 'Wallet balance retrieved successfully', 200, [
                'balance' => $balance,
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
            $user = User::find(Auth::id());
            $transactionService = resolve(TransactionService::class);

            $history = $transactionService->transactionHistory($request, $user);
            return ShopittPlus::response(true, 'Wallet transactions retrieved successfully', 200, $history);
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
    public function withdraw(WithdrawalRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $this->walletService->withdrawFunds($user, $request->validated(), $request->ip());
            return ShopittPlus::response(true, 'Withdrawal processed', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('WALLET WITHDRAWAL: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('WALLET WITHDRAWAL: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Withdrawal failed', 500);
        }
    }
}

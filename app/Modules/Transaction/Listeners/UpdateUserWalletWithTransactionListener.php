<?php

namespace App\Modules\Transaction\Listeners;

use App\Modules\Transaction\Events\FundWalletSuccessful;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Models\Wallet;
use App\Modules\Transaction\Notifications\WalletFundingSuccessfulNotification;
use App\Modules\Transaction\Services\TransactionService;
use App\Modules\Transaction\Services\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateUserWalletWithTransactionListener implements ShouldQueue
{
    public function __construct(
        public WalletService $walletService,
        public TransactionService $transactionService,
    ) {}

    public function handle(FundWalletSuccessful $event): void
    {
        $transaction = $event->transaction;
        $fees = $event->fees;
        $feeTransaction = $transaction->feeTransactions()->first();

        Log::info('UpdateUserWalletWithTransactionListener.handle() :' . json_encode($event));

        // 🔒 Find wallet first so we can lock by wallet ID
        $wallet = Wallet::where('user_id', $transaction->user_id)
        ->where('currency', $transaction->currency)
        ->with(['user'])
        ->first();

        if (!$wallet) {
            Log::error('UpdateUserWalletWithTransactionListener.handle() - Wallet not found for user id: ' . $transaction->user_id . ' and currency ' . $transaction->currency);
            return;
        }

        $walletId = $wallet->id;

        Cache::lock("wallet:{$walletId}", 10)->block(5, function () use (
            $wallet,
            $transaction,
            $feeTransaction,
            $fees,
        ) {
            try {
                DB::beginTransaction();
                $amount = $transaction->amount->getAmount()->toFloat();
                $fees = $fees + Transaction::WALLET_FUNDING_FEE;

                $this->walletService->deposit($wallet, $amount - $fees);

                $walletTransaction = $wallet->walletTransactions()->latest()->first();

                if (!$walletTransaction && $walletTransaction->wallet_id != $wallet->id && $walletTransaction->amount_change->getAmount()->toFloat() != $amount - $fees) {
                    Log::error('FundWalletProccessedListener.handle() - Could not find matching transaction for wallet: ' . $wallet->id);
                    return;
                }

                $transaction = $this->transactionService->updateTransactionStatus(
                    $transaction,
                    'SUCCESSFUL'
                );

                $feeTransaction = $this->transactionService->updateTransaction(
                    $feeTransaction,
                    [
                        'amount' => $fees
                    ]
                );
                $feeTransaction = $this->transactionService->updateTransactionStatus(
                    $feeTransaction,
                    'SUCCESSFUL'
                );

                $this->transactionService->attachWalletTransactionFor(
                    $transaction,
                    $wallet,
                    $walletTransaction->id
                );

                DB::commit();
                
                $transaction->user->notify(new WalletFundingSuccessfulNotification($transaction, $wallet));
                // (Add any needed notification here if you want)
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("UpdateUserWalletWithTransactionListener.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

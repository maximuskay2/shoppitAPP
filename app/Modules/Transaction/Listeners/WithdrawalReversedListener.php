<?php

namespace App\Modules\Transaction\Listeners;

use App\Modules\Transaction\Events\WithdrawalReversed;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Notifications\WithdrawalReversedNotification;
use App\Modules\Transaction\Services\TransactionService;
use App\Modules\Transaction\Services\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalReversedListener implements ShouldQueue
{
    public function __construct(
        public TransactionService $transactionService,
        public WalletService $walletService,
    ) {}

    public function handle(WithdrawalReversed $event): void
    {
        $transaction = $event->transaction;
        $fees = $event->fees;
        $feeTransaction = $transaction->feeTransactions()->first();

        Log::info('WithdrawalReversedListener.handle() :' . json_encode($event));

        $wallet = $transaction->wallet;
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

                // Reverse the withdrawal by depositing back to wallet
                $this->walletService->deposit($wallet, $amount + $fees);

                $walletTransaction = $wallet->walletTransactions()->latest()->first();

                if (!$walletTransaction || $walletTransaction->wallet_id != $wallet->id) {
                    Log::error('WithdrawalReversedListener.handle() - Could not find matching wallet transaction for wallet: ' . $wallet->id);
                    return;
                }

                $transaction = $this->transactionService->updateTransactionStatus(
                    $transaction,
                    'REVERSED'
                );

                $feeTransaction = $this->transactionService->updateTransaction(
                    $feeTransaction,
                    [
                        'amount' => $fees
                    ]
                );
                $feeTransaction = $this->transactionService->updateTransactionStatus(
                    $feeTransaction,
                    'REVERSED'
                );

                DB::commit();
                
                // Refresh wallet to get updated balance after reversal
                $wallet = $wallet->fresh();
                $transaction = Transaction::find($transaction->id)->load('wallet');
                $transaction->user->notify(new WithdrawalReversedNotification($transaction, $wallet));
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("WithdrawalReversedListener.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

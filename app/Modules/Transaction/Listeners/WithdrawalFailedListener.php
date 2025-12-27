<?php

namespace App\Modules\Transaction\Listeners;

use App\Modules\Transaction\Events\WithdrawalFailed;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Notifications\WithdrawalFailedNotification;
use App\Modules\Transaction\Services\TransactionService;
use App\Modules\Transaction\Services\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalFailedListener implements ShouldQueue
{
    public function __construct(
        public TransactionService $transactionService,
        public WalletService $walletService,
    ) {}

    public function handle(WithdrawalFailed $event): void
    {
        $transaction = $event->transaction;
        $fees = $event->fees;
        $feeTransaction = $transaction->feeTransactions()->first();

        Log::info('WithdrawalFailedListener.handle() :' . json_encode($event));

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

                $transaction = $this->transactionService->updateTransactionStatus(
                    $transaction,
                    'FAILED'
                );

                $feeTransaction = $this->transactionService->updateTransaction(
                    $feeTransaction,
                    [
                        'amount' => $fees
                    ]
                );
                $feeTransaction = $this->transactionService->updateTransactionStatus(
                    $feeTransaction,
                    'FAILED'
                );

                DB::commit();
                
                // Refresh wallet to get updated balance after reversal
                $wallet = $wallet->fresh();
                $transaction = Transaction::find($transaction->id)->load('wallet');
                $transaction->user->notify(new WithdrawalFailedNotification($transaction, $wallet));
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("WithdrawalFailedListener.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

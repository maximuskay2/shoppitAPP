<?php

namespace App\Modules\Transaction\Listeners;

use App\Modules\Transaction\Events\WithdrawalSuccessful;
use App\Modules\Transaction\Notifications\WithdrawalSuccessfulNotification;
use App\Modules\Transaction\Services\TransactionService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalSuccessfulListener implements ShouldQueue
{
    public function __construct(
        public TransactionService $transactionService,
    ) {}

    public function handle(WithdrawalSuccessful $event): void
    {
        $transactionId = $event->transaction->id;
        Log::info('WithdrawalSuccessfulListener.handle() :' . json_encode($event));
        
        Cache::lock("transaction:{$transactionId}", 10)->block(5, function () use ($event) {
            try {
                DB::beginTransaction();

                $transaction = $event->transaction;
                $fees = $event->fees;

                // Update main transaction status to SUCCESSFUL
                $this->transactionService->updateTransactionStatus($transaction, 'SUCCESSFUL');

                // Update fee transaction status to SUCCESSFUL if exists
                if ($transaction->feeTransactions()->exists()) {
                    $feeTransaction = $transaction->feeTransactions()->first();
                    $this->transactionService->updateTransactionStatus($feeTransaction, 'SUCCESSFUL');
                }

                DB::commit();

                // Reload transaction with relationships for notification
                $transaction = $transaction->fresh(['feeTransactions', 'wallet']);
                $user = $transaction->user;

                // Send notification
                $user->notify(new WithdrawalSuccessfulNotification($transaction));

                Log::info('Withdrawal marked as successful', [
                    'transaction_id' => $transaction->id,
                    'user_id' => $user->id,
                ]);

            } catch (Exception $e) {
                DB::rollBack();
                Log::error('WithdrawalSuccessfulListener.handle() - Failed to process withdrawal success', [
                    'transaction_id' => $event->transaction->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }
}

<?php

namespace App\Modules\Transaction\Listeners;

use App\Modules\Transaction\Events\WithdrawalProccessed;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Services\TransactionService;
use App\Modules\Transaction\Services\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalProccessedListener implements ShouldQueue
{
    public function __construct(
        public WalletService $walletService,
        public TransactionService $transactionService,
    ) {}

    public function handle(WithdrawalProccessed $event): void
    {
        $walletId = $event->wallet->id;
        Log::info('WithdrawalProccessedListener.handle() :' . json_encode($event));
        
        Cache::lock("wallet:{$walletId}", 10)->block(5, function () use ($event, $walletId) {
            try {
                DB::beginTransaction();

                $wallet = $event->wallet;
                $user = $wallet->user;
                $amount = $event->amount;
                $fees = $event->fees;
                $currency = $event->currency;
                $reference = $event->reference;
                $external_transaction_reference = $event->external_transaction_reference;
                $narration = $event->narration ?? null;
                $ip_address = $event->ip_address ?? null;
                $payload = $event->payload;
                
                $transaction = $this->transactionService->createPendingTransaction(
                    $user,
                    $amount,
                    $currency,
                    'SEND_MONEY',
                    $reference,
                    $payload,
                    $wallet->id,
                    $narration,
                    $ip_address,
                    $external_transaction_reference,
                );

                $feeTransaction = $this->transactionService->createPendingFeeTransaction(
                    $user,
                    $fees,
                    $currency,
                    'SEND_MONEY_FEE',
                    $reference,
                    $wallet->id,
                    $transaction->id
                );
                
                $transaction->feeTransactions()->save($feeTransaction);
                $transaction = Transaction::where('id', $transaction->id)
                    ->with(['feeTransactions'])
                    ->first();

                $this->walletService->debit($wallet, $event->amount + $event->fees);

                $walletTransaction = $wallet->walletTransactions()->latest()->first();

                if (!$walletTransaction && $walletTransaction->wallet_id != $wallet->id && $walletTransaction->amount_change->getAmount()->toFloat() != $event->amount + $event->fees) {
                    Log::error('OrderProcessedListener.handle() - Could not find matching transaction for wallet: ' . $wallet->id);
                    return;
                }

                $this->transactionService->attachWalletTransactionFor(
                    $transaction,
                    $wallet,
                    $walletTransaction->id
                );

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("WithdrawalProccessedListener.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

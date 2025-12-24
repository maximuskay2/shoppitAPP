<?php

namespace App\Modules\Transaction\Listeners;

use App\Modules\Transaction\Events\FundWalletProccessed;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Services\TransactionService;
use App\Modules\Transaction\Services\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FundWalletProccessedListener implements ShouldQueue
{
    public function __construct(
        public WalletService $walletService,
        public TransactionService $transactionService,
    ) {}

    public function handle(FundWalletProccessed $event): void
    {
        $walletId = $event->wallet->id;
        Log::info('FundWalletProccessedListener.handle() :' . json_encode($event));
        
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
                    'FUND_WALLET',
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
                    'FUND_WALLET_FEE',
                    $reference,
                    $wallet->id,
                    $transaction->id
                );

                $transaction->feeTransactions()->save($feeTransaction);
                $transaction = Transaction::where('id', $transaction->id)
                    ->with(['feeTransactions'])
                    ->first();

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("FundWalletProccessedListener.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

<?php

namespace App\Modules\Transaction\Console\Commands;

use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Services\TransactionService;
use Illuminate\Console\Command;

class FailPendingWalletFundingTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fail-pending-wallet-funding-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to fail pending wallet funding transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactions = Transaction::where('type', 'FUND_WALLET')
            ->where('status', 'PENDING')
            ->where('created_at', '<=', now()->subMinutes(5))
            ->get();

        foreach ($transactions as $transaction) {
            try {
                $transactionService = resolve(TransactionService::class);
                $transactionService->updateTransactionStatus($transaction, 'FAILED');
                $transactionService->updateTransactionStatus($transaction->feeTransactions()->first(), 'FAILED');
            }
            catch (\Exception $e) {
                // Log the error but continue processing other transactions
                continue;
            }
        }
    }
}

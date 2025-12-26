<?php

namespace App\Modules\Commerce\Listeners;

use App\Modules\Commerce\Events\OrderCancelled;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Notifications\OrderCancelledNotification;
use App\Modules\Commerce\Services\OrderService;
use App\Modules\Transaction\Services\TransactionService;
use App\Modules\Transaction\Services\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderCancelledListener implements ShouldQueue
{
    public function __construct(
        public OrderService $orderService,
        public WalletService $walletService,
        public TransactionService $transactionService,
    ) {}

    public function handle(OrderCancelled $event): void
    {
        $order = $event->order;

        Log::info('OrderCancelledListener.handle() :' . json_encode($event));

        Cache::lock("order:{$order->id}", 10)->block(5, function () use (
            $order,
        ) {
            try {
                DB::beginTransaction();

                $this->orderService->markOrderAsCancelled($order);

                $user = $order->user;
                $wallet = $user->wallet;
                $amount = $order->net_total_amount->getAmount()->toFloat() + $order->delivery_fee->getAmount()->toFloat();

                $this->walletService->deposit($wallet, $amount);

                $walletTransaction = $wallet->walletTransactions()->latest()->first();

                if (!$walletTransaction && $walletTransaction->wallet_id != $wallet->id && $walletTransaction->amount_change->getAmount()->toFloat() != $amount) {
                    Log::error('FundWalletProccessedListener.handle() - Could not find matching transaction for wallet: ' . $wallet->id);
                    return;
                }

                $transaction = $this->transactionService->createSuccessfulTransaction(
                    user: $user,
                    wallet_id: $wallet->id,
                    amount: $amount,
                    type: 'ORDER_REFUND',
                    currency: $order->currency,
                    reference: $order->id,
                );

                $this->transactionService->createSuccessfulFeeTransaction(
                    user: $user,
                    amount: 0.0,
                    currency: $order->currency,
                    type: 'ORDER_REFUND_FEE',
                    principal_transaction_id: $transaction->id,
                    wallet_id: $wallet->id,
                );

                DB::commit();

                $order = Order::find($order->id)->load('lineItems.product', 'user', 'vendor');
                $order->user->notify(new OrderCancelledNotification($order, $transaction, $wallet));
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("OrderCancelledListener.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

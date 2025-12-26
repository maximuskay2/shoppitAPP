<?php

namespace App\Modules\Commerce\Listeners;

use App\Modules\Commerce\Events\OrderCompleted;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Notifications\OrderCompletedNotification;
use App\Modules\Commerce\Notifications\OrderCompletedVendorNotification;
use App\Modules\Commerce\Services\OrderService;
use App\Modules\Transaction\Services\SettlementService;
use App\Modules\Transaction\Services\TransactionService;
use App\Modules\Transaction\Services\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderCompletedListener implements ShouldQueue
{
    public function __construct(
        public OrderService $orderService,
        public WalletService $walletService,
        public TransactionService $transactionService,
        public SettlementService $settlementService,
    ) {}

    public function handle(OrderCompleted $event): void
    {
        $order = $event->order;

        Log::info('OrderCompletedListener.handle() :' . json_encode($event));

        Cache::lock("order:{$order->id}", 10)->block(5, function () use (
            $order,
        ) {
            try {
                DB::beginTransaction();

                $this->orderService->markOrderAsCompleted($order);

                $user = $order->vendor->user;
                $wallet = $user->wallet;
                $amount = $order->net_total_amount->getAmount()->toFloat() + $order->delivery_fee->getAmount()->toFloat();
                $commissionRate = Order::COMMISSION_RATE;
                $commission = ($commissionRate / 100) * $amount;
                $payableAmount = $amount - $commission;

                $this->walletService->deposit($wallet, $payableAmount);

                $walletTransaction = $wallet->walletTransactions()->latest()->first();

                if (!$walletTransaction && $walletTransaction->wallet_id != $wallet->id && $walletTransaction->amount_change->getAmount()->toFloat() != $payableAmount) {
                    Log::error('FundWalletProccessedListener.handle() - Could not find matching transaction for wallet: ' . $wallet->id);
                    return;
                }

                $transaction = $this->transactionService->createSuccessfulTransaction(
                    user: $user,
                    wallet_id: $wallet->id,
                    amount: $payableAmount,
                    type: 'ORDER_SETTLEMENT',
                    currency: $order->currency,
                    reference: $order->id,
                );

                $this->transactionService->createSuccessfulFeeTransaction(
                    user: $user,
                    amount: 0.0,
                    currency: $order->currency,
                    type: 'ORDER_SETTLEMENT_FEE',
                    principal_transaction_id: $transaction->id,
                    wallet_id: $wallet->id,
                );

                $settlement = $this->settlementService->createSuccessfulSettlement(
                    order: $order,
                    vendor_id: $order->vendor->id,
                    total_amount: $amount,
                    platform_fee: $commission,
                    vendor_amount: $payableAmount,
                    payment_gateway: $order->payment_processor,
                    currency: $order->currency,
                );

                $order = Order::find($order->id)->load('lineItems.product', 'user', 'vendor');
                $order->user->notify(new OrderCompletedNotification($order));
                $order->vendor->user->notify(new OrderCompletedVendorNotification($order, $settlement, $wallet));
                DB::commit();

            } catch (Exception $e) {
                DB::rollBack();
                Log::error("OrderCompletedListener.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

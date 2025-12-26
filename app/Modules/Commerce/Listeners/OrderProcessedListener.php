<?php

namespace App\Modules\Commerce\Listeners;

use App\Modules\Commerce\Events\OrderProcessed;
use App\Modules\Commerce\Models\Cart;
use App\Modules\Commerce\Models\CartVendor;
use App\Modules\Commerce\Models\Coupon;
use App\Modules\Commerce\Models\CouponUsage;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Notifications\OrderPaidWithWalletNotification;
use App\Modules\Commerce\Notifications\OrderReceivedNotification;
use App\Modules\Commerce\Services\OrderService;
use App\Modules\Transaction\Services\TransactionService;
use App\Modules\Transaction\Services\WalletService;
use App\Modules\User\Models\User;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderProcessedListener implements ShouldQueue
{
    public function __construct(
        public OrderService $orderService,
        public TransactionService $transactionService,
        public WalletService $walletService,
    ) {}

    public function handle(OrderProcessed $event): void
    {
        $cartVendorId = $event->cartVendorId;
        Log::info('OrderProcessedListener.handle() : ' . json_encode($event));
        
        Cache::lock("order:{$event->userId}:{$cartVendorId}", 10)->block(5, function () use ($event, $cartVendorId) {
            try {
                DB::beginTransaction();

                // Fetch user
                $user = User::find($event->userId);
                $wallet = $user->wallet;
                if (!$user) {
                    throw new Exception("User not found: {$event->userId}");
                }

                // Fetch cart vendor
                $cartVendor = CartVendor::with(['items.product', 'coupon'])
                    ->find($cartVendorId);

                if (!$cartVendor) {
                    throw new Exception("Cart vendor not found: {$cartVendorId}");
                }

                // Create pending order
                $order = $this->orderService->createOrder(
                    user: $user,
                    vendorId: $event->vendorId,
                    grossTotal: $event->grossTotal,
                    couponDiscount: $event->couponDiscount,
                    netTotal: $event->netTotal,
                    deliveryFee: $event->deliveryFee,
                    currency: $event->currency,
                    paymentReference: $event->paymentReference,
                    couponId: $event->couponId,
                    couponCode: $event->couponCode,
                    processorTransactionId: $event->processorTransactionId,
                    receiverDeliveryAddress: $event->receiverDeliveryAddress,
                    receiverName: $event->receiverName,
                    receiverEmail: $event->receiverEmail,
                    receiverPhone: $event->receiverPhone,
                    orderNotes: $event->orderNotes,
                    isGift: $event->isGift,
                    status: $event->walletUsage ? 'PAID' : 'PENDING'
                );

                // Create order line items
                $this->orderService->createOrderLineItems($order, $cartVendor);

                if ($event->walletUsage) {
                    $transaction = $this->transactionService->createSuccessfulTransaction(
                        user: $user,
                        amount: $event->netTotal + $event->deliveryFee,
                        currency: $event->currency,
                        type: 'ORDER_PAYMENT',
                        reference: $event->paymentReference,
                        wallet_id: $wallet->id,
                        narration: 'Payment for Order ' . $order->tracking_id,
                        userIp: $event->ipAddress,
                    );

                    $this->transactionService->createSuccessfulFeeTransaction(
                        user: $user,
                        amount: 0.0,
                        currency: $event->currency,
                        type: 'ORDER_PAYMENT_FEE',
                        principal_transaction_id: $transaction->id,
                        wallet_id: $wallet->id,
                    );

                    $this->walletService->debit($wallet, $event->netTotal + $event->deliveryFee);

                    $walletTransaction = $wallet->walletTransactions()->latest()->first();

                    if (!$walletTransaction && $walletTransaction->wallet_id != $wallet->id && $walletTransaction->amount_change->getAmount()->toFloat() != $event->netTotal + $event->deliveryFee) {
                        Log::error('OrderProcessedListener.handle() - Could not find matching transaction for wallet: ' . $wallet->id);
                        return;
                    }

                    $this->transactionService->attachWalletTransactionFor(
                        $transaction,
                        $wallet,
                        $walletTransaction->id
                    );

                    $order = Order::find($order->id)->load('lineItems.product', 'user', 'vendor');
                    $user->notify(new OrderPaidWithWalletNotification($order, $transaction, $wallet));
                    $order->vendor->user->notify(new OrderReceivedNotification($order));
                }

                // Create coupon usage record if coupon was applied
                if ($event->couponId) {
                    $coupon = Coupon::find($event->couponId);
                    
                    if ($coupon) {
                        CouponUsage::create([
                            'coupon_id' => $coupon->id,
                            'user_id' => $user->id,
                            'order_id' => $order->id,
                            'discount_amount' => $event->couponDiscount,
                        ]);

                        // Increment coupon usage count
                        $coupon->increment('usage_count');
                    }
                }

                // Clear the cart vendor after successful order creation
                $cartVendor->items()->delete();
                $cartVendor->delete();

                Log::info('Order created successfully', [
                    'order_id' => $order->id,
                    'tracking_id' => $order->tracking_id,
                    'payment_reference' => $order->payment_reference,
                ]);

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("OrderProcessedListener.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

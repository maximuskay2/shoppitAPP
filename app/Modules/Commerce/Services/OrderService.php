<?php

namespace App\Modules\Commerce\Services;

use App\Modules\Commerce\Events\OrderCompleted;
use App\Modules\Commerce\Models\CartVendor;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\OrderLineItems;
use App\Modules\User\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

use function Symfony\Component\Clock\now;

class OrderService
{
    public function index(User $user, $request)
    {
        $query = Order::where('user_id', $user->id);

        // Filter by status if provided
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
        
        return $query->with(['lineItems.product', 'vendor.user', 'user'])->latest()->cursorPaginate(20);
    }

    public function getOrderById(User $user, string $orderId): Order
    {
        $order = Order::where('user_id', $user->id)
            ->where('id', $orderId)
            ->with(['lineItems.product', 'vendor', 'user'])
            ->first();

        if (!$order) {
            throw new \InvalidArgumentException("OrderService.getOrderById(): Order not found for ID: $orderId.");
        }

        return $order;
    }

    public function updateStatus(User $user, string $orderId, array $data): Order
    {
        $order = $this->getOrderById($user, $orderId);

        if ($order->status === 'PENDING' || $order->status === 'PROCESSING') {
            throw new InvalidArgumentException("Cannot update status of a pending or processing order.");
        }

        if ($order->status === 'PAID') {
            throw new InvalidArgumentException("User cannot update status of a paid order.");
        }

        if ($order->status === 'CANCELLED' || $order->status === 'REFUNDED' || $order->status === 'COMPLETED') {
            throw new InvalidArgumentException("Cannot update status of a cancelled, refunded, or completed order.");
        }

        if ($order->status === 'DISPATCHED' && !in_array($data['status'], ['COMPLETED'])) {
            throw new InvalidArgumentException("Invalid status transition from DISPATCHED to {$data['status']}.");
        }

        if ($data['status'] === 'COMPLETED') {
            event(new OrderCompleted($order));
        }
        
        return $order;
    }

    /**
     * Update order status to paid
     */
    public function markOrderAsPaid(Order $order): bool
    {
        $order->update([
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        Log::info('Order payment completed', [
            'order_id' => $order->id,
        ]);

        return true;
    }

    public function markOrderAsDispatched(Order $order): bool
    {
        $order->update([
            'status' => 'DISPATCHED',
            'dispatched_at' => now(),
        ]);

        Log::info('Order marked as dispatched', [
            'order_id' => $order->id,
        ]);

        return true;
    }

    public function markOrderAsCancelled(Order $order): bool
    {
        $order->update([
            'status' => 'CANCELLED',
            'cancelled_at' => now(),
        ]);

        Log::info('Order marked as cancelled', [
            'order_id' => $order->id,
        ]);

        return true;
    }

    public function markOrderAsCompleted(Order $order): bool
    {
        $order->update([
            'status' => 'COMPLETED',
            'completed_at' => now(),
            'settled_at' => now(),
        ]);

        Log::info('Order marked as completed', [
            'order_id' => $order->id,
        ]);

        return true;
    }

    /**
     * Update order status to failed
     */
    public function markOrderAsFailed(string $paymentReference, string $reason = null): bool
    {
        $order = Order::where('payment_reference', $paymentReference)->first();

        if (!$order) {
            Log::warning('Order not found for failed payment reference', ['reference' => $paymentReference]);
            return false;
        }

        $order->update([
            'status' => 'failed',
        ]);

        Log::info('Order payment failed', [
            'order_id' => $order->id,
            'reference' => $paymentReference,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Get order by payment reference
     */
    public function getOrderByPaymentReference(string $paymentReference): ?Order
    {
        return Order::where('payment_reference', $paymentReference)->first();
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Order $order, string $status): ?Order
    {

        if (!in_array($status, ["PAID", "FAILED", "PENDING", "PROCESSING", "CANCELLED", "REFUNDED", "DISPATCHED", "COMPLETED"])) {
            throw new \Exception("OrderService.updateOrderStatus(): Invalid status: $status.");
        }

        $order->update([
            'status' => $status,
        ]);

        return $order;
    }

    /**
     * Create and return a new pending order
     *
     * @param User $user
     * @param string $vendorId
     * @param float $grossTotal
     * @param float $couponDiscount
     * @param float $netTotal
     * @param string $currency
     * @param string $paymentReference
     * @param ?string $couponId
     * @param ?string $couponCode
     * @param ?string $processorTransactionId
     * @param ?string $receiverDeliveryAddress
     * @param ?string $receiverName
     * @param ?string $receiverEmail
     * @param ?string $receiverPhone
     * @param ?string $orderNotes
     * @param bool $isGift
     * 
     * @return Order
     */
    public function createOrder(
        User $user,
        string $vendorId,
        float $grossTotal,
        float $couponDiscount,
        float $netTotal,
        float $deliveryFee,
        string $currency,
        string $paymentReference,
        string $status = 'PENDING',
        ?string $couponId = null,
        ?string $couponCode = null,
        ?string $processorTransactionId = null,
        ?string $receiverDeliveryAddress = null,
        ?string $receiverName = null,
        ?string $receiverEmail = null,
        ?string $receiverPhone = null,
        ?string $orderNotes = null,
        bool $isGift = false
    ): Order {
        $trackingId = Str::upper(Str::random(12));

        $order = Order::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'vendor_id' => $vendorId,
            'coupon_id' => $couponId,
            'coupon_code' => $couponCode,
            'coupon_discount' => Money::of($couponDiscount, $currency),
            'payment_reference' => $paymentReference,
            'processor_transaction_id' => $processorTransactionId ?? 'null',
            'status' => $status,
            'paid_at' => $status === 'PAID' ? now() : null,
            'email' => $user->email,
            'tracking_id' => 'ORD-' . $trackingId,
            'order_notes' => $orderNotes,
            'is_gift' => $isGift,
            'receiver_delivery_address' => $receiverDeliveryAddress,
            'receiver_name' => $receiverName ?? $user->name,
            'receiver_email' => $receiverEmail ?? $user->email,
            'receiver_phone' => $receiverPhone,
            'currency' => $currency,
            'delivery_fee' => Money::of($deliveryFee, $currency),
            'gross_total_amount' => Money::of($grossTotal, $currency),
            'net_total_amount' => Money::of($netTotal, $currency),
        ]);

        return $order;
    }

    /**
     * Create order line items from cart vendor items
     *
     * @param Order $order
     * @param CartVendor $cartVendor
     * @return void
     */
    public function createOrderLineItems(Order $order, CartVendor $cartVendor): void
    {
        foreach ($cartVendor->items as $cartItem) {
            OrderLineItems::create([
                'id' => Str::uuid(),
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->price,
                'subtotal' => $cartItem->subtotal,
                'type' => 'product',
            ]);
        }
    }
}
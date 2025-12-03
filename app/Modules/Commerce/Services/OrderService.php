<?php

namespace App\Modules\Commerce\Services;

use App\Modules\Commerce\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Update order status to paid
     */
    public function markOrderAsPaid(string $paymentReference, array $paymentData): bool
    {
        $order = Order::where('payment_reference', $paymentReference)->first();

        if (!$order) {
            Log::warning('Order not found for payment reference', ['reference' => $paymentReference]);
            return false;
        }

        $order->update([
            'status' => 'paid',
            'processor_transaction_id' => $paymentData['id'],
            'paid_at' => $paymentData['paid_at'],
        ]);

        Log::info('Order payment completed', [
            'order_id' => $order->id,
            'reference' => $paymentReference,
            'amount' => $paymentData['amount'],
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
    public function updateOrderStatus(Order $order, string $status, array $additionalData = []): bool
    {
        $updateData = ['status' => $status] + $additionalData;

        return $order->update($updateData);
    }
}
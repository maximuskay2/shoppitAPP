<?php

namespace App\Modules\Commerce\Services\Vendor;

use App\Modules\Commerce\Events\OrderCancelled;
use App\Modules\Commerce\Events\OrderDispatched;
use App\Modules\Commerce\Models\CartVendor;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\OrderLineItems;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

use function Symfony\Component\Clock\now;

class OrderService
{
    public function index(Vendor $vendor, $request)
    {        
        $query = Order::where('vendor_id', $vendor->id);

        // Filter by status if provided
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
        
        return $query->with(['lineItems.product', 'vendor', 'user'])->latest()->cursorPaginate(20);
    }

    public function getOrderById(Vendor $vendor, string $orderId): Order
    {
        $order = Order::where('vendor_id', $vendor->id)
            ->where('id', $orderId)
            ->with(['lineItems.product', 'vendor', 'user'])
            ->first();

        if (!$order) {
            throw new \InvalidArgumentException("OrderService.getOrderById(): Order not found for ID: $orderId.");
        }

        return $order;
    }

    public function settlements(Vendor $vendor)
    {
        return $vendor->settlements()->with(['order.lineItems.product', 'order.user'])->latest()->cursorPaginate(20);
    }

    public function orderStatisticsSummary(Vendor $vendor, $request)
    {
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $query = Order::where('vendor_id', $vendor->id)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        // Total orders count
        $totalOrders = $query->count();

        // Orders by status
        $pendingOrders = (clone $query)->where('status', 'PENDING')->count();
        $processingOrders = (clone $query)->where('status', 'PROCESSING')->count();
        $paidOrders = (clone $query)->where('status', 'PAID')->count();
        $dispatchedOrders = (clone $query)->where('status', 'DISPATCHED')->count();
        $completedOrders = (clone $query)->where('status', 'COMPLETED')->count();
        $cancelledOrders = (clone $query)->where('status', 'CANCELLED')->count();
        $refundedOrders = (clone $query)->where('status', 'REFUNDED')->count();
        $failedOrders = (clone $query)->where('status', 'FAILED')->count();

        // Total revenue from completed orders (gross total + delivery fee)
        $totalRevenue = (clone $query)
            ->where('status', 'COMPLETED')
            ->get()
            ->sum(function ($order) {
                return $order->gross_total_amount->getAmount()->toFloat() + $order->delivery_fee->getAmount()->toFloat();
            });

        // Total settlements (vendor amount)
        $totalSettlements = $vendor->settlements()
            ->whereMonth('settled_at', $month)
            ->whereYear('settled_at', $year)
            ->where('status', 'SUCCESSFUL')
            ->get()
            ->sum(function ($settlement) {
                return $settlement->vendor_amount->getAmount()->toFloat();
            });

        // Total platform fees
        $totalPlatformFees = $vendor->settlements()
            ->whereMonth('settled_at', $month)
            ->whereYear('settled_at', $year)
            ->where('status', 'SUCCESSFUL')
            ->get()
            ->sum(function ($settlement) {
                return $settlement->platform_fee->getAmount()->toFloat();
            });

        // Total amount pending settlement (paid + dispatched orders not yet completed)
        $pendingSettlement = Order::where('vendor_id', $vendor->id)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->whereIn('status', ['PAID', 'DISPATCHED'])
            ->get()
            ->sum(function ($order) {
                return $order->gross_total_amount->getAmount()->toFloat() + $order->delivery_fee->getAmount()->toFloat();
            });

        // Calculate average order value
        $averageOrderValue = $completedOrders > 0 ? $totalRevenue / $completedOrders : 0;

        return [
            'period' => [
                'month' => $month,
                'year' => $year,
            ],
            'orders' => [
                'total' => $totalOrders,
                'pending' => $pendingOrders,
                'processing' => $processingOrders,
                'paid' => $paidOrders,
                'dispatched' => $dispatchedOrders,
                'completed' => $completedOrders,
                'cancelled' => $cancelledOrders,
                'refunded' => $refundedOrders,
                'failed' => $failedOrders,
            ],
            'revenue' => [
                'total_revenue' => number_format($totalRevenue, 2),
                'total_settlements' => number_format($totalSettlements, 2),
                'total_platform_fees' => number_format($totalPlatformFees, 2),
                'pending_settlement' => number_format($pendingSettlement, 2),
                'average_order_value' => number_format($averageOrderValue, 2),
            ],
            'currency' => 'NGN',
        ];
    }

    public function updateStatus(Vendor $vendor, string $orderId, array $data): Order
    {
        $order = $this->getOrderById($vendor, $orderId);

        if ($order->status === 'PENDING' || $order->status === 'PROCESSING') {
            throw new InvalidArgumentException("Cannot update status of a pending or processing order.");
        }

        if ($order->status === 'CANCELLED' || $order->status === 'REFUNDED' || $order->status === 'COMPLETED' || $order->status === 'DISPATCHED') {
            throw new InvalidArgumentException("Cannot update status of a cancelled, dispatched, or completed order.");
        }

        if ($order->status === 'PAID' && !in_array($data['status'], ['DISPATCHED', 'CANCELLED'])) {
            throw new InvalidArgumentException("Invalid status transition from PAID to {$data['status']}.");
        }

        if ($data['status'] === 'DISPATCHED') {
            event(new OrderDispatched($order));
        }

        if ($data['status'] === 'CANCELLED') {
            event(new OrderCancelled($order));
        }

        return $order;
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
}
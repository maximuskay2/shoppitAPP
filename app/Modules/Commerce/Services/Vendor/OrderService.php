<?php

namespace App\Modules\Commerce\Services\Vendor;

use App\Helpers\GeoHelper;
use App\Modules\Commerce\Events\OrderCancelled;
use App\Modules\Commerce\Events\OrderDispatched;
use App\Modules\Commerce\Events\OrderStatusUpdated;
use App\Modules\Commerce\Events\DriverNotificationBroadcast;
use App\Modules\Commerce\Models\CartVendor;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\OrderLineItems;
use App\Modules\Commerce\Models\DeliveryRadius;
use App\Modules\Commerce\Notifications\Driver\OrderReadyForPickupNotification;
use App\Modules\Commerce\Notifications\Driver\OrderCancelledNotification;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use App\Modules\User\Models\DriverLocation;
use App\Modules\Commerce\Services\OrderStatusStateMachine;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

use function Symfony\Component\Clock\now;

class OrderService
{
    public function __construct(private readonly OrderStatusStateMachine $stateMachine) {}
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
        $dispatchedOrders = (clone $query)->whereIn('status', ['DISPATCHED', 'OUT_FOR_DELIVERY'])->count();
        $completedOrders = (clone $query)->whereIn('status', ['COMPLETED', 'DELIVERED'])->count();
        $cancelledOrders = (clone $query)->where('status', 'CANCELLED')->count();
        $refundedOrders = (clone $query)->where('status', 'REFUNDED')->count();
        $failedOrders = (clone $query)->where('status', 'FAILED')->count();

        // Total revenue from completed orders (gross total + delivery fee)
        $totalRevenue = (clone $query)
            ->whereIn('status', ['COMPLETED', 'DELIVERED'])
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
            ->whereIn('status', ['PAID', 'READY_FOR_PICKUP', 'PICKED_UP', 'OUT_FOR_DELIVERY', 'DISPATCHED'])
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

        $this->stateMachine->assertTransition($order->status, $data['status']);

        $order->update([
            'status' => $data['status'],
        ]);

        if ($data['status'] === 'READY_FOR_PICKUP') {
            // Get drivers within delivery radius
            $driversInRadius = $this->getDriversWithinDeliveryRadius($order);

            // Send notifications to drivers within radius with retry strategy
            $this->notifyDriversWithRetry($driversInRadius, $order, 'order.ready_for_pickup');

            // Broadcast to each driver
            foreach ($driversInRadius as $driver) {
                event(new DriverNotificationBroadcast(
                    $driver->id,
                    'order.ready_for_pickup',
                    ['order_id' => $order->id]
                ));
            }
        }

        if ($data['status'] === 'DISPATCHED') {
            event(new OrderDispatched($order));
        }

        if ($data['status'] === 'CANCELLED') {
            event(new OrderCancelled($order));

            if ($order->driver) {
                $order->driver->notify(new OrderCancelledNotification($order));
                event(new DriverNotificationBroadcast(
                    $order->driver->id,
                    'order.cancelled',
                    ['order_id' => $order->id]
                ));
            }
        }

        event(new OrderStatusUpdated($order));

        return $order;
    }

    /**
     * Get all verified, online drivers within delivery radius of the order vendor
     */
    private function getDriversWithinDeliveryRadius(Order $order): array
    {
        try {
            // Get delivery radius settings
            $radiusConfig = DeliveryRadius::where('name', 'default')->first();
            $radiusActive = $radiusConfig ? (bool) $radiusConfig->is_active : true;
            $radiusKm = $radiusConfig && $radiusConfig->radius_km !== null
                ? (float) $radiusConfig->radius_km
                : GeoHelper::getActiveDeliveryRadius();

            // If radius filtering is disabled, return all online verified drivers
            if (!$radiusActive) {
                return User::whereHas('driver', function ($query) {
                    $query->where('is_verified', true)->where('is_online', true);
                })->get()->all();
            }

            // Get vendor location
            $vendorLat = (float) $order->vendor->latitude;
            $vendorLon = (float) $order->vendor->longitude;

            // Get the last location for each verified, online driver
            $usersWithLatestLocation = DB::table('users')
                ->join('drivers', 'users.id', '=', 'drivers.user_id')
                ->leftJoin('driver_locations', function ($join) {
                    $join->on('users.id', '=', 'driver_locations.user_id')
                        ->where('driver_locations.id', DB::raw(
                            '(SELECT MAX(id) FROM driver_locations WHERE driver_locations.user_id = users.id)'
                        ));
                })
                ->where('drivers.is_verified', true)
                ->where('drivers.is_online', true)
                ->select('users.id', 'driver_locations.latitude', 'driver_locations.longitude')
                ->get();

            $driversInRadius = [];

            foreach ($usersWithLatestLocation as $userLocation) {
                // If driver has no location data, skip
                if (is_null($userLocation->latitude) || is_null($userLocation->longitude)) {
                    Log::warn('Driver ' . $userLocation->id . ' has no location data, skipping notification');
                    continue;
                }

                $driverLat = (float) $userLocation->latitude;
                $driverLon = (float) $userLocation->longitude;

                // Check if driver is within radius
                if (GeoHelper::isWithinDeliveryRadius($driverLat, $driverLon, $vendorLat, $vendorLon, $radiusKm)) {
                    $user = User::find($userLocation->id);
                    if ($user) {
                        $driversInRadius[] = $user;
                    }
                }
            }

            Log::info('Found ' . count($driversInRadius) . ' drivers within delivery radius for order ' . $order->id);

            return $driversInRadius;
        } catch (\Exception $e) {
            Log::error('Error getting drivers within delivery radius: ' . $e->getMessage());
            // Fallback to all online drivers if error occurs
            return User::whereHas('driver', function ($query) {
                $query->where('is_verified', true)->where('is_online', true);
            })->get()->all();
        }
    }

    /**
     * Send notifications to drivers with retry on failure
     *
     * @param array $drivers Drivers to notify
     * @param Order $order Order instance
     * @param string $notificationType Type of notification (e.g., 'order.ready_for_pickup')
     * @return void
     */
    private function notifyDriversWithRetry(array $drivers, Order $order, string $notificationType): void
    {
        $notification = match ($notificationType) {
            'order.ready_for_pickup' => new OrderReadyForPickupNotification($order),
            'order.cancelled' => new OrderCancelledNotification($order),
            default => null,
        };

        if (!$notification) {
            Log::warn('Unknown notification type: ' . $notificationType);
            return;
        }

        // Send notifications with built-in queue retry
        try {
            Notification::send($drivers, $notification);
            Log::info('Notifications queued for ' . count($drivers) . ' drivers for order ' . $order->id);
        } catch (\Exception $e) {
            Log::error('Error queuing driver notifications: ' . $e->getMessage());
        }
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

        $this->stateMachine->assertTransition($order->status, $status);

        $order->update([
            'status' => $status,
        ]);

        return $order;
    }
}
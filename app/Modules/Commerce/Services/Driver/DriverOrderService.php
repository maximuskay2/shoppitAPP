<?php

namespace App\Modules\Commerce\Services\Driver;

use App\Helpers\GeoHelper;
use App\Modules\Commerce\Events\OrderCompleted;
use App\Modules\Commerce\Events\OrderDispatched;
use App\Modules\Commerce\Events\OrderStatusUpdated;
use App\Modules\Commerce\Models\DeliveryRadius;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Settings;
use App\Modules\Transaction\Models\DriverEarning;
use App\Modules\User\Models\AuditLog;
use App\Modules\User\Models\DriverLocation;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DriverOrderService
{
    private const GEOFENCE_RADIUS_KM = 300.0;

    /**
     * Get available orders for a driver
     * Filters by pickup readiness and optionally by delivery radius
     *
     * @param object $request Request with optional vendor_id, latitude, longitude
     * @return \Illuminate\Pagination\CursorPaginate
     */
    public function availableOrders($request)
    {
        $query = Order::query()
            ->whereNull('driver_id')
            ->where('status', 'READY_FOR_PICKUP');

        // Optional: Filter by vendor
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->input('vendor_id'));
        }

        // Optional: Filter by delivery radius if driver coordinates provided
        if ($request->has('latitude') && $request->has('longitude')) {
            $driverLat = (float) $request->input('latitude');
            $driverLon = (float) $request->input('longitude');
            $radiusConfig = DeliveryRadius::where('name', 'default')->first();
            $radiusActive = $radiusConfig ? (bool) $radiusConfig->is_active : true;
            $radiusKm = $radiusConfig && $radiusConfig->radius_km !== null
                ? (float) $radiusConfig->radius_km
                : GeoHelper::getActiveDeliveryRadius();

            if (!$radiusActive) {
                return $query->with(['lineItems.product', 'vendor.user', 'user'])
                    ->latest('orders.created_at')
                    ->cursorPaginate(20);
            }

            // Get bounding box for initial filtering (performance optimization)
            $boundingBox = GeoHelper::getBoundingBox($driverLat, $driverLon, $radiusKm);

            // Join with vendors and filter by radius
            $query = $query->join('vendors', 'orders.vendor_id', '=', 'vendors.id')
                ->whereBetween('vendors.latitude', [$boundingBox['lat_min'], $boundingBox['lat_max']])
                ->whereBetween('vendors.longitude', [$boundingBox['lon_min'], $boundingBox['lon_max']])
                ->select('orders.*')
                ->where(DB::raw(
                    "SQRT(POW(69.1 * (vendors.latitude - {$driverLat}), 2) + POW(69.1 * ({$driverLon} - vendors.longitude) * COS(vendors.latitude / 57.3), 2))"
                ), '<=', $radiusKm);
        } else {
            // Join with vendors for relationship loading
            $query = $query->join('vendors', 'orders.vendor_id', '=', 'vendors.id')
                ->select('orders.*');
        }

        return $query->with(['lineItems.product', 'vendor.user', 'user'])->latest('orders.created_at')->cursorPaginate(20);
    }

    public function acceptOrder(User $driver, string $orderId): Order
    {
        return DB::transaction(function () use ($driver, $orderId) {
            $order = Order::where('id', $orderId)->lockForUpdate()->first();

            if (!$order) {
                throw new InvalidArgumentException('Order not found.');
            }

            if (!is_null($order->driver_id)) {
                throw new InvalidArgumentException('Order already assigned to a driver.');
            }

            if ($order->status !== 'READY_FOR_PICKUP') {
                throw new InvalidArgumentException('Order is not ready for pickup.');
            }

            $order->update([
                'driver_id' => $driver->id,
                'assigned_at' => now(),
            ]);

            return $order->fresh(['lineItems.product', 'vendor.user', 'user']);
        });
    }

    public function rejectOrder(User $driver, string $orderId, string $reason): Order
    {
        return DB::transaction(function () use ($driver, $orderId, $reason) {
            $order = Order::where('id', $orderId)
                ->where('driver_id', $driver->id)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new InvalidArgumentException('Order not found for this driver.');
            }

            if ($order->status !== 'READY_FOR_PICKUP') {
                throw new InvalidArgumentException('Order cannot be rejected at this stage.');
            }

            $order->update([
                'driver_id' => null,
                'assigned_at' => null,
            ]);

            AuditLog::create([
                'actor_id' => $driver->id,
                'actor_type' => 'driver',
                'action' => 'driver.order.rejected',
                'auditable_type' => Order::class,
                'auditable_id' => $order->id,
                'meta' => [
                    'reason' => $reason,
                    'status' => $order->status,
                ],
            ]);

            return $order->fresh(['lineItems.product', 'vendor.user', 'user']);
        });
    }

    public function markPickedUp(User $driver, string $orderId): Order
    {
        $order = $this->getAssignedOrder($driver, $orderId);

        if ($order->status !== 'READY_FOR_PICKUP') {
            throw new InvalidArgumentException('Order cannot be marked as picked up.');
        }

        $this->assertPickupGeofence($driver, $order);

        $order->update([
            'status' => 'PICKED_UP',
            'picked_up_at' => now(),
        ]);

        event(new OrderStatusUpdated($order));

        return $order->fresh(['lineItems.product', 'vendor.user', 'user']);
    }

    public function startDelivery(User $driver, string $orderId): Order
    {
        $order = $this->getAssignedOrder($driver, $orderId);

        if ($order->status !== 'PICKED_UP') {
            throw new InvalidArgumentException('Order cannot be set to out for delivery.');
        }

        $order->update([
            'status' => 'OUT_FOR_DELIVERY',
            'dispatched_at' => now(),
        ]);

        event(new OrderDispatched($order));
        event(new OrderStatusUpdated($order));

        return $order->fresh(['lineItems.product', 'vendor.user', 'user']);
    }

    public function deliverOrder(User $driver, string $orderId, ?string $otpCode): Order
    {
        $order = $this->getAssignedOrder($driver, $orderId);

        if ($order->status !== 'OUT_FOR_DELIVERY') {
            throw new InvalidArgumentException('Order cannot be delivered yet.');
        }

        $this->assertDeliveryGeofence($driver, $order);

        if ($order->otp_code && $order->otp_code !== $otpCode) {
            throw new InvalidArgumentException('Invalid delivery OTP.');
        }

        $order->update([
            'status' => 'DELIVERED',
            'delivered_at' => now(),
        ]);

        $this->recordEarnings($order, $driver);

        event(new OrderCompleted($order));
        event(new OrderStatusUpdated($order));

        return $order->fresh(['lineItems.product', 'vendor.user', 'user']);
    }

    public function activeOrder(User $driver): ?Order
    {
        return Order::where('driver_id', $driver->id)
            ->whereIn('status', ['READY_FOR_PICKUP', 'PICKED_UP', 'OUT_FOR_DELIVERY'])
            ->latest('assigned_at')
            ->first()?->load(['lineItems.product', 'vendor.user', 'user']);
    }

    public function orderHistory(User $driver, $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 100);

        return Order::where('driver_id', $driver->id)
            ->where('status', 'DELIVERED')
            ->with(['lineItems.product', 'vendor.user', 'user'])
            ->latest('delivered_at')
            ->cursorPaginate($perPage);
    }

    private function getAssignedOrder(User $driver, string $orderId): Order
    {
        $order = Order::where('id', $orderId)
            ->where('driver_id', $driver->id)
            ->first();

        if (!$order) {
            throw new InvalidArgumentException('Order not found for this driver.');
        }

        return $order;
    }

    private function assertPickupGeofence(User $driver, Order $order): void
    {
        $order->loadMissing('vendor');
        $vendor = $order->vendor;

        if (!$vendor || $vendor->latitude === null || $vendor->longitude === null) {
            throw new InvalidArgumentException('Vendor location is not available for pickup validation.');
        }

        $location = $this->getLatestDriverLocation($driver);

        $this->assertWithinRadius(
            $location->lat,
            $location->lng,
            (float) $vendor->latitude,
            (float) $vendor->longitude,
            self::GEOFENCE_RADIUS_KM,
            'Driver must be within 300 km of the vendor to pick up the order.'
        );
    }

    private function assertDeliveryGeofence(User $driver, Order $order): void
    {
        if ($order->delivery_latitude === null || $order->delivery_longitude === null) {
            throw new InvalidArgumentException('Delivery location is not available for delivery validation.');
        }

        $location = $this->getLatestDriverLocation($driver);

        $this->assertWithinRadius(
            $location->lat,
            $location->lng,
            (float) $order->delivery_latitude,
            (float) $order->delivery_longitude,
            self::GEOFENCE_RADIUS_KM,
            'Driver must be within 300 km of the delivery address to complete the order.'
        );
    }

    private function getLatestDriverLocation(User $driver): DriverLocation
    {
        $location = DriverLocation::where('user_id', $driver->id)
            ->orderBy('recorded_at', 'desc')
            ->first();

        if (!$location) {
            throw new InvalidArgumentException('Driver location not available for geo-fence validation.');
        }

        return $location;
    }

    private function assertWithinRadius(
        float $driverLat,
        float $driverLng,
        float $targetLat,
        float $targetLng,
        float $maxDistanceKm,
        string $errorMessage
    ): void {
        $distanceKm = GeoHelper::calculateDistance($driverLat, $driverLng, $targetLat, $targetLng);

        if ($distanceKm > $maxDistanceKm) {
            throw new InvalidArgumentException($errorMessage);
        }
    }

    private function recordEarnings(Order $order, User $driver): void
    {
        $existing = DriverEarning::where('order_id', $order->id)->first();
        if ($existing) {
            return;
        }

        if (!$order->delivery_fee) {
            return;
        }

        $commissionRate = Settings::getValue('driver_commission_rate');
        $commissionRate = $commissionRate !== null ? (float) $commissionRate : Order::COMMISSION_RATE;

        $gross = $order->delivery_fee->getAmount()->toFloat();
        $commission = round($gross * ($commissionRate / 100), 2);
        $net = max(0, $gross - $commission);

        DriverEarning::create([
            'driver_id' => $driver->id,
            'order_id' => $order->id,
            'gross_amount' => $gross,
            'commission_amount' => $commission,
            'net_amount' => $net,
            'currency' => $order->delivery_fee->getCurrency()->getCurrencyCode(),
            'status' => 'PENDING',
        ]);
    }
}

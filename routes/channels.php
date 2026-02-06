<?php

use App\Modules\Commerce\Models\Order;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;

Broadcast::channel('order.tracking.{orderId}', function ($user, string $orderId) {
    $order = Order::with('vendor')->find($orderId);
    if (!$order || !$user) {
        return false;
    }

    $vendorUserId = $order->vendor?->user_id;

    return $order->user_id === $user->id
        || $order->driver_id === $user->id
        || ($vendorUserId && $vendorUserId === $user->id);
});

Broadcast::channel('order.status.{orderId}', function ($user, string $orderId) {
    $order = Order::with('vendor')->find($orderId);
    if (!$order || !$user) {
        return false;
    }

    $vendorUserId = $order->vendor?->user_id;

    return $order->user_id === $user->id
        || $order->driver_id === $user->id
        || ($vendorUserId && $vendorUserId === $user->id);
});

Broadcast::channel('driver.notifications.{driverId}', function ($user, string $driverId) {
    return $user && $user->id === $driverId;
});

Broadcast::channel('admin.fleet.locations', function () {
    return Auth::guard('admin-api')->check();
});

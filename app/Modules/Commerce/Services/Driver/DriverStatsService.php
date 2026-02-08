<?php

namespace App\Modules\Commerce\Services\Driver;

use App\Modules\Commerce\Models\Order;
use App\Modules\Transaction\Models\DriverEarning;
use App\Modules\User\Models\User;

class DriverStatsService
{
    public function summary(User $driver): array
    {
        $totalAssigned = Order::where('driver_id', $driver->id)->count();
        $totalDelivered = Order::where('driver_id', $driver->id)
            ->whereIn('status', ['DELIVERED', 'COMPLETED'])
            ->count();
        $totalCancelled = Order::where('driver_id', $driver->id)
            ->where('status', 'CANCELLED')
            ->count();

        $completionRate = $totalAssigned > 0
            ? round(($totalDelivered / $totalAssigned) * 100, 2)
            : 0.0;

        $earningsTotal = DriverEarning::where('driver_id', $driver->id)
            ->get()
            ->sum(fn ($earning) => $earning->net_amount->getAmount()->toFloat());

        $earningsPending = DriverEarning::where('driver_id', $driver->id)
            ->where('status', 'PENDING')
            ->get()
            ->sum(fn ($earning) => $earning->net_amount->getAmount()->toFloat());

        $earningsPaid = DriverEarning::where('driver_id', $driver->id)
            ->where('status', 'PAID')
            ->get()
            ->sum(fn ($earning) => $earning->net_amount->getAmount()->toFloat());

        $lastDelivery = Order::where('driver_id', $driver->id)
            ->whereIn('status', ['DELIVERED', 'COMPLETED'])
            ->latest('delivered_at')
            ->value('delivered_at');

        return [
            'total_assigned' => $totalAssigned,
            'total_delivered' => $totalDelivered,
            'total_cancelled' => $totalCancelled,
            'completion_rate' => $completionRate,
            'earnings_total' => $earningsTotal,
            'earnings_pending' => $earningsPending,
            'earnings_paid' => $earningsPaid,
            'last_delivery_at' => $lastDelivery,
        ];
    }
}

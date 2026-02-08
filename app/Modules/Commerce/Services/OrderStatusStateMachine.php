<?php

namespace App\Modules\Commerce\Services;

use InvalidArgumentException;

class OrderStatusStateMachine
{
    private const TRANSITIONS = [
        'PENDING' => ['PAID', 'FAILED', 'CANCELLED'],
        'PROCESSING' => ['PAID', 'FAILED', 'CANCELLED'],
        'PAID' => ['READY_FOR_PICKUP', 'DISPATCHED', 'CANCELLED', 'REFUNDED'],
        'READY_FOR_PICKUP' => ['PICKED_UP', 'CANCELLED'],
        'PICKED_UP' => ['OUT_FOR_DELIVERY'],
        'OUT_FOR_DELIVERY' => ['DELIVERED'],
        'DISPATCHED' => ['OUT_FOR_DELIVERY', 'DELIVERED'],
        'DELIVERED' => ['COMPLETED'],
        'COMPLETED' => [],
        'CANCELLED' => [],
        'REFUNDED' => [],
        'FAILED' => [],
    ];

    public function assertTransition(string $from, string $to): void
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if (!array_key_exists($from, self::TRANSITIONS)) {
            throw new InvalidArgumentException("Unknown order status: {$from}.");
        }

        if (!in_array($to, self::TRANSITIONS[$from], true)) {
            throw new InvalidArgumentException("Invalid status transition from {$from} to {$to}.");
        }
    }
}

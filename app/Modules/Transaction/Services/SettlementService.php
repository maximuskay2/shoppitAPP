<?php

namespace App\Modules\Transaction\Services;

use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Settlement;

class SettlementService
{
    /**
     * Create and return a new successful settlement
     *
     * @param Order $order
     * @param float $total_amount
     * @param float $platform_fee
     * @param float $vendor_amount
     * @param string $payment_gateway
     * @param string $currency
     * 
     * @return Settlement
     */
    public function createSuccessfulSettlement(
        Order $order,
        $vendor_id,
        $total_amount,
        $platform_fee,
        $vendor_amount,
        $payment_gateway,
        $currency = 'NGN',
    ) {
        $settlement = Settlement::create([
            "order_id" => $order->id,
            "vendor_id" => $vendor_id,
            "total_amount" => $total_amount,
            "platform_fee" => $platform_fee,
            "vendor_amount" => $vendor_amount,
            "payment_gateway" => $payment_gateway,
            "status" => "SUCCESSFUL",
            "settled_at" => now(),
            "currency" => $currency,
        ]);

        return $settlement;
    }
}


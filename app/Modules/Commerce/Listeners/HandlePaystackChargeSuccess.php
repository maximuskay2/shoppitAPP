<?php

namespace App\Modules\Commerce\Listeners;

use App\Modules\Commerce\Models\Order;
use App\Modules\Transaction\Events\PaystackChargeSuccessEvent;
use Illuminate\Support\Facades\Log;

class HandlePaystackChargeSuccess
{
    public function handle(PaystackChargeSuccessEvent $event)
    {
        $data = $event->paymentData;
        $reference = $data['reference'];

        $order = Order::where('payment_reference', $reference)->first();

        if (!$order) {
            Log::warning('Order not found for payment reference', ['reference' => $reference]);
            return;
        }

        // Verify amounts match (convert order amount to kobo for comparison)
        $orderAmountInKobo = $order->net_total_amount * 100;
        $transactionAmount = $data['amount'];

        if ($orderAmountInKobo != $transactionAmount) {
            Log::error('Order amount mismatch', [
                'order_id' => $order->id,
                'order_amount' => $orderAmountInKobo,
                'transaction_amount' => $transactionAmount,
                'reference' => $reference,
            ]);
            return;
        }

        $order->update([
            'status' => 'paid',
            'processor_transaction_id' => $data['id'],
            'paid_at' => $data['paid_at'],
        ]);

        Log::info('Order payment completed', [
            'order_id' => $order->id,
            'reference' => $reference,
            'amount' => $data['amount'],
        ]);
    }
}
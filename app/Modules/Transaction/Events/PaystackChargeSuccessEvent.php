<?php

namespace App\Modules\Transaction\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaystackChargeSuccessEvent
{
    use Dispatchable, SerializesModels;

    public array $paymentData;

    public function __construct(array $paymentData)
    {
        $this->paymentData = $paymentData;
        \Log::info('PaystackChargeSuccessEvent fired', ['paymentData' => $paymentData]);
    }
}
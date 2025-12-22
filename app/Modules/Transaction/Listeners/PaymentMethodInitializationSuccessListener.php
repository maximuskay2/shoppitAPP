<?php

namespace App\Modules\Transaction\Listeners;

use App\Modules\Transaction\Events\PaymentMethodInitializationSuccess;
use App\Modules\Transaction\Services\PaymentMethodService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentMethodInitializationSuccessListener implements ShouldQueue
{
    public function __construct(
        public PaymentMethodService $paymentMethodService,
    ) {}

    public function handle(PaymentMethodInitializationSuccess $event): void
    {
        $user = $event->user;
        $paymentMethod = $event->paymentMethod;
        $customer_code = $event->customerCode;
        $authorization_code = $event->authorizationCode;
        $email = $event->email;
        $currency = $event->currency;
        $externalTransactionReference = $event->externalTransactionReference;
        $expiryMonth = $event->expiryMonth;
        $expiryYear = $event->expiryYear;
        $lastFour = $event->lastFour;
        $cardType = $event->cardType;
        $bank = $event->bank;
        $brand = $event->brand;
        $accountName = $event->accountName;

        Log::info('PaymentMethodInitializationSuccessListener.handle() :', [
            'user' => $user,
            'paymentMethod' => $paymentMethod
        ]);

        Cache::lock("paymentMethod:{$paymentMethod->id}", 10)->block(5, function () use ($paymentMethod, $customer_code, $authorization_code, $currency, $expiryMonth, $expiryYear, $lastFour, $cardType, $bank, $brand, $accountName) {
            try {
                DB::beginTransaction();

                $this->paymentMethodService->add(
                    $paymentMethod,
                    [
                        'customer_code' => $customer_code,
                        'authorization_code' => $authorization_code,
                        'currency' => $currency,
                        'expiry_month' => $expiryMonth,
                        'expiry_year' => $expiryYear,
                        'last_four' => $lastFour,
                        'card_type' => $cardType,
                        'bank' => $bank,
                        'brand' => $brand,
                        'account_name' => $accountName
                    ]
                );
                
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("PaymentMethodInitializationSuccessListener.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

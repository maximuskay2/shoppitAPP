<?php

namespace App\Modules\Transaction\Services;

use App\Modules\Commerce\Dtos\ServiceProviderDto;
use App\Modules\Commerce\Models\Service;
use App\Modules\Transaction\Models\Subscription;
use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\Transaction\Models\SubscriptionRecord;
use App\Modules\Transaction\Services\External\PaystackService;
use App\Modules\User\Models\Vendor;
use Exception;

class PaymentService 
{
    public $payment_service_provider;
    public $paystackService;

    public function __construct ()
    {
        $payment_service = Service::where('name', 'payments')->first();

        if (!$payment_service) {
            throw new Exception('Payment service not found');
        }
        
        if ($payment_service->status === false) {
            throw new Exception('Payment service is currently unavailable');
        }
        $this->payment_service_provider = $payment_service->providers->where('status', true)->first();
        
        if (is_null($this->payment_service_provider)) {
            throw new Exception('Payment service provider not found');
        }

        $this->paystackService = app(PaystackService::class);
    }

    public function createPlan(object $data)
    {
        $provider = $this->getPaymentServiceProvider();

        if ($provider->name == 'paystack') {
            return $this->paystackService->createPlan($data);
        }
    }

    public function subscribe(Vendor $vendor, SubscriptionRecord $record, SubscriptionPlan $plan)
    {
        $provider = $this->getPaymentServiceProvider();

        if ($provider->name == 'paystack') {
            return $this->paystackService->subscribe($vendor, $record, $plan);
        }
    }

    public function upgradeSubscription(Vendor $vendor, SubscriptionRecord $record, SubscriptionPlan $plan)
    {
        $provider = $this->getPaymentServiceProvider();

        if ($provider->name == 'paystack') {
            return $this->paystackService->upgradeSubscription($vendor, $record, $plan);
        }
    }

    public function updatePaymentMethod(Subscription $subscription)
    {
        $provider = $this->getPaymentServiceProvider();

        if ($provider->name == 'paystack') {
            return $this->paystackService->updatePaymentMethod($subscription);
        }
    }

    public function cancelSubscription(Vendor $vendor, Subscription $subscription)
    {
        $provider = $this->getPaymentServiceProvider();

        if ($provider->name == 'paystack') {
            return $this->paystackService->cancelSubscription($vendor, $subscription);
        }
    }

    private function getPaymentServiceProvider()
    {
        if (!$this->payment_service_provider) {
            throw new Exception('Payment service provider not found');
        }
    
        $provider = ServiceProviderDto::from($this->payment_service_provider);

        if (!$provider instanceof ServiceProviderDto) {
            $provider = new ServiceProviderDto(
                name: $provider->name ?? null,
                description: $provider->description ?? null,
                status: $provider->status ?? false,
                percentage_charge: $provider->percentage_charge ?? 0.00,
                fixed_charge: $provider->fixed_charge ?? 0.00,
            );
        }

        return $provider;
    }
}
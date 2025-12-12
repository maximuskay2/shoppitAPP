<?php

namespace App\Modules\User\Services;

use App\Modules\Commerce\Dtos\ServiceProviderDto;
use App\Modules\Commerce\Models\Service;
use App\Modules\Transaction\Services\External\QoreidService;
use Exception;

class IdentityService 
{
    public $identity_service_provider;
    public $qoreidService;

    public function __construct ()
    {
        $identity_service = Service::where('name', 'identity')->first();

        if (!$identity_service) {
            throw new Exception('Identity service not found');
        }
        
        if ($identity_service->status === false) {
            throw new Exception('Identity service is currently unavailable');
        }
        $this->identity_service_provider = $identity_service->providers->where('status', true)->first();
        
        if (is_null($this->identity_service_provider)) {
            throw new Exception('Identity service provider not found');
        }

        $this->qoreidService = app(QoreidService::class);
    }

    public function verifyBusiness(object $data)
    {
        $provider = $this->getIdentityServiceProvider();

        if ($provider->name == 'qoreid') {
            return $this->qoreidService->verifyBusiness($data);
        }
    }

    private function getIdentityServiceProvider()
    {
        if (!$this->identity_service_provider) {
            throw new Exception('Identity service provider not found');
        }
    
        $provider = ServiceProviderDto::from($this->identity_service_provider);

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
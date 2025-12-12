<?php

namespace App\Modules\Transaction\Services\External;

use App\Modules\User\Enums\UserKYBStatusEnum;
use App\Modules\User\Services\VendorService;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class QoreidService
{
     /**
     * The base URL for Qoreid API.
     *
     * @var string
     */
    private static $baseUrl;

    /**
     * QoreidService constructor.
     *
     * @param string $baseUrl The base URL for Qoreid API.
     */
    public function __construct(string $baseUrl)
    {
        self::$baseUrl = $baseUrl;
    }

    public function verifyBusiness(object $verification_data) {
        try {
            // $url = self::$baseUrl . '/v1/ng/identities/tin/' . $verification_data->tin;

            // $response = Http::talkToSafehaven($url, 'GET');
            // $response_data = $response['data'] ?? null;

            // if (strtolower($response_data['summary']['tin_check']) !== 'verified') {
            //     throw new InvalidArgumentException('Error verifying TIN: Invalid number or other verification error.');
            // }

            // if (strtolower($response_data['tin']['cacRegNo']) !== strtolower($verification_data->cac)) {
            //     throw new InvalidArgumentException('Error verifying TIN: CAC Reg no mismatch');
            // }

            // if (strtolower($response_data['tin']['email']) !== strtolower($verification_data->email)) {
            //     throw new InvalidArgumentException('Error verifying TIN: Email mismatch');
            // }

            // $cac_url = self::$baseUrl . '/v1/ng/identities/cac-basic';            
            // $data = [
            //     'regNumber' => $verification_data->cac,
            // ];

            // $cac_response = Http::talkToSafehaven($cac_url, 'POST', $data);
            // $cac_response_data = $cac_response['data'] ?? null;
            
            // if (strtolower($cac_response_data['summary']['cac_check']) !== 'verified') {
            //     throw new InvalidArgumentException('Error verifying CAC: Invalid number or other verification error.');
            // }

            // if (strtolower($cac_response_data['cac']['companyName']) !== strtolower($verification_data->business_name)) {
            //     throw new InvalidArgumentException('Error verifying CAC: Business name mismatch');
            // }

            $vendorService = resolve(VendorService::class);
            $vendorService->updateVendorAccount($verification_data->vendor, [
                'kyb_status' => UserKYBStatusEnum::SUCCESSFUL,
                'cac' => Crypt::encryptString($verification_data->cac),
                'tin' => Crypt::encryptString($verification_data->tin)
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('Error Encountered at Verify CAC/TIN method in Qoreid Service: ' . $e->getMessage());
            throw $e;
        }
    }
}
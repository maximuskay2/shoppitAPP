<?php

namespace App\Modules\Transaction\Services;

use App\Modules\Transaction\Models\PaymentMethod;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Illuminate\Support\Facades\DB;

class PaymentDetailService
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function index (Vendor $vendor)
    {
        return $vendor->paymentDetails;
    }

    public function listBanks()
    {
        try {
            $response = $this->paymentService->listBanks();
            

            return array_map(function ($bank) {
                return [
                    'name' => $bank['name'],
                    'code' => $bank['code'],
                ];
            }, $response['data']);
        } catch (\Exception $e) {
            throw new \Exception('Failed to list banks: ' . $e->getMessage());
        }
    }

    public function resolveAccount(array $data)
    {
        try {
            $response = $this->paymentService->resolveAccount($data);

            return [
                'account_name' => $response['data']['account_name'],
                'account_number' => $response['data']['account_number'],
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to resolve account: ' . $e->getMessage());
        }
    }

    public function store (Vendor $vendor, array $data)
    {
        try {
            DB::beginTransaction();

            $response = $this->paymentService->createTransferRecipient($data);

            $paymentDetail = $vendor->paymentDetails()->create([
                'paystack_recipient_code' => $response['recipient_code'],
                'bank_code' => $response['details']['bank_code'],
                'bank_name' => $response['details']['bank_name'],
                'account_number' => $response['details']['account_number'],
                'account_name' => $response['details']['account_name'],
            ]);

            DB::commit();
            return $paymentDetail;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to initialize payment method: ' . $e->getMessage());
        }
    }

    public function add (PaymentMethod $paymentMethod, array $data)
    {
        $paymentMethod->update($data);
    }

    public function destroy (Vendor $vendor, string $paymentDetailId): void
    {
        try {
            DB::beginTransaction();

            $paymentDetail = $vendor->paymentDetails()->where('id', $paymentDetailId)->first();
    
            if (!$paymentDetail) {
                throw new \InvalidArgumentException('Payment detail not found.');
            }

            $this->paymentService->deleteTransferRecipient($paymentDetail->paystack_recipient_code);

            $paymentDetail->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to initialize payment method: ' . $e->getMessage());
        }
    }
}
<?php

namespace App\Modules\Transaction\Services;

use App\Modules\User\Models\DriverPaymentDetail;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;

class DriverPaymentDetailService
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function show(User $driver): ?DriverPaymentDetail
    {
        return DriverPaymentDetail::where('driver_id', $driver->id)->first();
    }

    public function listBanks(): array
    {
        $response = $this->paymentService->listBanks();

        return array_map(function ($bank) {
            return [
                'name' => $bank['name'],
                'code' => $bank['code'],
            ];
        }, $response['data']);
    }

    public function resolveAccount(array $data): array
    {
        $response = $this->paymentService->resolveAccount($data);

        return [
            'account_name' => $response['account_name'],
            'account_number' => $response['account_number'],
            'bank_code' => $data['bank_code'],
        ];
    }

    public function store(User $driver, array $data): DriverPaymentDetail
    {
        try {
            DB::beginTransaction();

            $response = $this->paymentService->createTransferRecipient($data);

            $payload = [
                'driver_id' => $driver->id,
                'paystack_recipient_code' => $response['recipient_code'] ?? null,
                'bank_code' => $response['details']['bank_code'] ?? $data['bank_code'],
                'bank_name' => $response['details']['bank_name'] ?? null,
                'account_number' => $response['details']['account_number'] ?? $data['account_number'],
                'account_name' => $response['details']['account_name'] ?? $data['account_name'],
                'recipient_meta' => $response ?? null,
            ];

            $detail = DriverPaymentDetail::updateOrCreate(
                ['driver_id' => $driver->id],
                $payload
            );

            DB::commit();

            return $detail;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to store driver payment detail: ' . $e->getMessage());
        }
    }
}

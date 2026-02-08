<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\DriverResolveAccountNumberRequest;
use App\Http\Requests\Api\V1\Driver\DriverStorePaymentDetailRequest;
use App\Modules\Transaction\Services\DriverPaymentDetailService;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaymentDetailsController extends Controller
{
    public function __construct(private readonly DriverPaymentDetailService $paymentDetailService) {}

    public function show(): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            $detail = $driver ? $this->paymentDetailService->show($driver) : null;

            return ShopittPlus::response(true, 'Driver payment detail retrieved successfully', 200, $detail);
        } catch (InvalidArgumentException $e) {
            Log::error('DRIVER PAYMENT DETAIL: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DRIVER PAYMENT DETAIL: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve payment detail', 500);
        }
    }

    public function listBanks(): JsonResponse
    {
        try {
            $banks = $this->paymentDetailService->listBanks();
            return ShopittPlus::response(true, 'Banks retrieved successfully', 200, $banks);
        } catch (InvalidArgumentException $e) {
            Log::error('DRIVER LIST BANKS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DRIVER LIST BANKS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve banks', 500);
        }
    }

    public function resolveAccount(DriverResolveAccountNumberRequest $request): JsonResponse
    {
        try {
            $accountDetails = $this->paymentDetailService->resolveAccount($request->validated());
            return ShopittPlus::response(true, 'Account details retrieved successfully', 200, $accountDetails);
        } catch (InvalidArgumentException $e) {
            Log::error('DRIVER RESOLVE ACCOUNT NUMBER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DRIVER RESOLVE ACCOUNT NUMBER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve account details', 500);
        }
    }

    public function store(DriverStorePaymentDetailRequest $request): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            if (!$driver) {
                return ShopittPlus::response(false, 'Driver not found', 404);
            }

            $paymentDetail = $this->paymentDetailService->store($driver, $request->validated());
            return ShopittPlus::response(true, 'Payment detail stored successfully', 200, $paymentDetail);
        } catch (InvalidArgumentException $e) {
            Log::error('DRIVER STORE PAYMENT DETAIL: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DRIVER STORE PAYMENT DETAIL: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to store payment detail', 500);
        }
    }
}

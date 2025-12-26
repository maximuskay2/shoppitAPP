<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vendor\ResolveAccountNumberRequest;
use App\Http\Requests\Api\V1\Vendor\StorePaymentDetailRequest;
use App\Modules\Transaction\Services\PaymentDetailService;
use App\Http\Resources\Transaction\PaymentDetailResource;
use App\Modules\User\Models\User;
use Faker\Provider\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaymentDetailsController extends Controller
{

    public function __construct(private readonly PaymentDetailService $paymentDetailService) {}

    public function index(): JsonResponse
    {
        try {
            $user = User::find(Auth::id());
            $vendor = $user->vendor;

            $paymentDetails = $this->paymentDetailService->index($vendor);
            return ShopittPlus::response(true, 'Payment details retrieved successfully', 200, PaymentDetailResource::collection($paymentDetails));
        } catch (InvalidArgumentException $e) {
            Log::error('GET VENDOR PAYMENT DETAILS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('GET VENDOR PAYMENT DETAILS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve payment details', 500);
        }
    }

    public function listBanks(): JsonResponse
    {
        try {
            $banks = $this->paymentDetailService->listBanks();
            return ShopittPlus::response(true, 'Banks retrieved successfully', 200, $banks);
        } catch (InvalidArgumentException $e) {
            Log::error('LIST BANKS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('LIST BANKS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve banks', 500);
        }
    }

    public function resolveAccount(ResolveAccountNumberRequest $request): JsonResponse
    {
        try {
            $accountDetails = $this->paymentDetailService->resolveAccount($request->validated());
            return ShopittPlus::response(true, 'Account details retrieved successfully', 200, $accountDetails);
        } catch (InvalidArgumentException $e) {
            Log::error('RESOLVE ACCOUNT NUMBER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('RESOLVE ACCOUNT NUMBER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve account details', 500);
        }
    }

    public function store(StorePaymentDetailRequest $request): JsonResponse
    {
        try {
            $user = User::find(Auth::id());
            $vendor = $user->vendor;

            $paymentDetail = $this->paymentDetailService->store($vendor, $request->validated());
            return ShopittPlus::response(true, 'Payment detail stored successfully', 200, PaymentDetailResource::make($paymentDetail));
        } catch (InvalidArgumentException $e) {
            Log::error('STORE PAYMENT DETAIL: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('STORE PAYMENT DETAIL: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to store payment detail', 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = User::find(Auth::id());
            $vendor = $user->vendor;

            $this->paymentDetailService->destroy($vendor, $id);
            return ShopittPlus::response(true, 'Payment detail deleted successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('DESTROY PAYMENT DETAIL: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DESTROY PAYMENT DETAIL: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete payment detail', 500);
        }
    }


}
<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Transaction\PaymentMethodResource;
use App\Modules\Transaction\Services\PaymentMethodService;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaymentMethodController extends Controller
{
    public function __construct(private readonly PaymentMethodService $paymentMethodService) {}

    public function index(): JsonResponse
    {
        try {
            $user = User::find(Auth::id());

            $paymentMethods = $this->paymentMethodService->index($user);

            return ShopittPlus::response(true, 'Payment methods retrieved successfully', 200, PaymentMethodResource::collection($paymentMethods));
        } catch (InvalidArgumentException $e) {
            Log::error('GET PAYMENT METHODS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('GET PAYMENT METHODS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve payment methods', 500);
        }
    }

    public function initialize(): JsonResponse
    {
        try {
            $user = User::find(Auth::id());

            $response = $this->paymentMethodService->initialize($user);
            return ShopittPlus::response(true, 'Payment methods initialized successfully', 200, $response);
        } catch (InvalidArgumentException $e) {
            Log::error('INITIALIZE PAYMENT METHODS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('INITIALIZE PAYMENT METHODS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to initialize payment method', 500);
        }
    }

    public function destroy(string $paymentMethodId): JsonResponse
    {
        try {
            $user = User::find(Auth::id());

            $this->paymentMethodService->destroy($user, $paymentMethodId);
            return ShopittPlus::response(true, 'Payment method deleted successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('DELETE PAYMENT METHOD: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DELETE PAYMENT METHOD: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete payment method', 500);
        }
    }
}
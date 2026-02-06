<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Driver\ApprovePayoutRequest;
use App\Modules\Transaction\Services\Admin\DriverPayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class DriverPayoutController extends Controller
{
    public function __construct(private readonly DriverPayoutService $driverPayoutService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $payouts = $this->driverPayoutService->listPayouts($request);

            return ShopittPlus::response(true, 'Driver payouts retrieved successfully', 200, $payouts);
        } catch (\Exception $e) {
            Log::error('LIST DRIVER PAYOUTS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve driver payouts', 500);
        }
    }

    public function approve(ApprovePayoutRequest $request, string $id): JsonResponse
    {
        try {
            $payout = $this->driverPayoutService->approvePayout($id, $request->input('reference'));

            return ShopittPlus::response(true, 'Payout approved successfully', 200, $payout);
        } catch (InvalidArgumentException $e) {
            Log::error('APPROVE DRIVER PAYOUT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('APPROVE DRIVER PAYOUT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to approve payout', 500);
        }
    }
}

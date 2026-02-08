<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\DriverPayoutRequest;
use App\Modules\Commerce\Models\Settings;
use App\Modules\Transaction\Models\DriverEarning;
use App\Modules\Transaction\Models\DriverPayout;
use App\Modules\User\Models\User;
use Brick\Money\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DriverPayoutController extends Controller
{
    public function balance(): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            $currency = Settings::getValue('currency') ?? 'NGN';

            $pending = DriverEarning::where('driver_id', $driver->id)
                ->where('status', 'PENDING')
                ->sum('net_amount');

            $amount = Money::ofMinor((int) $pending, $currency)->getAmount()->toFloat();

            return ShopittPlus::response(true, 'Balance retrieved successfully', 200, [
                'withdrawable' => $amount,
                'currency' => $currency,
            ]);
        } catch (\Exception $e) {
            Log::error('DRIVER PAYOUT BALANCE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve payout balance', 500);
        }
    }

    public function index(): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());

            $payouts = DriverPayout::where('driver_id', $driver->id)
                ->latest()
                ->get()
                ->map(function (DriverPayout $payout) {
                    return $this->mapPayout($payout);
                });

            return ShopittPlus::response(true, 'Driver payouts retrieved successfully', 200, $payouts);
        } catch (\Exception $e) {
            Log::error('DRIVER PAYOUT LIST: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve payouts', 500);
        }
    }

    public function request(DriverPayoutRequest $request): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            $currency = Settings::getValue('currency') ?? 'NGN';

            $existingRequest = DriverPayout::where('driver_id', $driver->id)
                ->where('status', 'PENDING')
                ->latest()
                ->first();

            if ($existingRequest) {
                return ShopittPlus::response(false, 'A payout request is already pending', 409);
            }

            $pendingEarnings = DriverEarning::where('driver_id', $driver->id)
                ->where('status', 'PENDING')
                ->get();

            if ($pendingEarnings->isEmpty()) {
                return ShopittPlus::response(false, 'No pending earnings available', 400);
            }

            $total = $pendingEarnings->sum(function (DriverEarning $earning) {
                return $earning->net_amount->getAmount()->toFloat();
            });

            $payout = DriverPayout::create([
                'driver_id' => $driver->id,
                'amount' => $total,
                'currency' => $currency,
                'status' => 'PENDING',
            ]);

            DriverEarning::whereIn('id', $pendingEarnings->pluck('id'))
                ->update(['payout_id' => $payout->id]);

            return ShopittPlus::response(true, 'Payout request submitted', 201, $this->mapPayout($payout));
        } catch (\Exception $e) {
            Log::error('DRIVER PAYOUT REQUEST: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to submit payout request', 500);
        }
    }

    private function mapPayout(DriverPayout $payout): array
    {
        return [
            'id' => $payout->id,
            'amount' => $payout->amount->getAmount()->toFloat(),
            'currency' => $payout->amount->getCurrency()->getCurrencyCode(),
            'status' => $payout->status,
            'reference' => $payout->reference,
            'paid_at' => $payout->paid_at,
            'created_at' => $payout->created_at,
        ];
    }
}

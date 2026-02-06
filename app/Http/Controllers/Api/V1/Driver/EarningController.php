<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Commerce\Models\Settings;
use App\Modules\Transaction\Models\DriverEarning;
use App\Modules\Transaction\Models\DriverPayout;
use App\Modules\User\Models\User;
use Brick\Money\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EarningController extends Controller
{
    public function summary(): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            $currency = Settings::getValue('currency') ?? 'NGN';

            $totals = DriverEarning::where('driver_id', $driver->id)
                ->selectRaw('SUM(gross_amount) as gross_total, SUM(commission_amount) as commission_total, SUM(net_amount) as net_total')
                ->first();

            $pending = DriverEarning::where('driver_id', $driver->id)
                ->where('status', 'PENDING')
                ->sum('net_amount');

            $paid = DriverEarning::where('driver_id', $driver->id)
                ->where('status', 'PAID')
                ->sum('net_amount');

            $data = [
                'currency' => $currency,
                'totals' => [
                    'gross' => Money::ofMinor((int) ($totals->gross_total ?? 0), $currency)->getAmount()->toFloat(),
                    'commission' => Money::ofMinor((int) ($totals->commission_total ?? 0), $currency)->getAmount()->toFloat(),
                    'net' => Money::ofMinor((int) ($totals->net_total ?? 0), $currency)->getAmount()->toFloat(),
                ],
                'by_status' => [
                    'pending' => Money::ofMinor((int) $pending, $currency)->getAmount()->toFloat(),
                    'paid' => Money::ofMinor((int) $paid, $currency)->getAmount()->toFloat(),
                ],
                'counts' => [
                    'total' => DriverEarning::where('driver_id', $driver->id)->count(),
                    'pending' => DriverEarning::where('driver_id', $driver->id)->where('status', 'PENDING')->count(),
                    'paid' => DriverEarning::where('driver_id', $driver->id)->where('status', 'PAID')->count(),
                ],
            ];

            return ShopittPlus::response(true, 'Driver earnings summary retrieved successfully', 200, $data);
        } catch (\Exception $e) {
            Log::error('DRIVER EARNINGS SUMMARY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve earnings summary', 500);
        }
    }

    public function history(Request $request): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            $currency = Settings::getValue('currency') ?? 'NGN';
            $perPage = min((int) $request->input('per_page', 20), 100);

            $earnings = DriverEarning::where('driver_id', $driver->id)
                ->with('order')
                ->latest()
                ->cursorPaginate($perPage);

            $items = $earnings->getCollection()->map(function (DriverEarning $earning) use ($currency) {
                $order = $earning->order;
                return [
                    'id' => $earning->id,
                    'order_id' => $earning->order_id,
                    'tracking_id' => $order?->tracking_id,
                    'gross_amount' => $earning->gross_amount->getAmount()->toFloat(),
                    'commission_amount' => $earning->commission_amount->getAmount()->toFloat(),
                    'net_amount' => $earning->net_amount->getAmount()->toFloat(),
                    'currency' => $earning->currency ?? $currency,
                    'status' => $earning->status,
                    'payout_id' => $earning->payout_id,
                    'created_at' => $earning->created_at,
                ];
            });

            $data = [
                'data' => $items,
                'next_cursor' => $earnings->nextCursor()?->encode(),
                'prev_cursor' => $earnings->previousCursor()?->encode(),
                'has_more' => $earnings->hasMorePages(),
                'per_page' => $earnings->perPage(),
            ];

            return ShopittPlus::response(true, 'Driver earnings history retrieved successfully', 200, $data);
        } catch (\Exception $e) {
            Log::error('DRIVER EARNINGS HISTORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve earnings history', 500);
        }
    }
}

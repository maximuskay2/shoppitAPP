<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Driver\ApprovePayoutRequest;
use App\Modules\Transaction\Services\Admin\DriverPayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
            if (config('logging.channels.slack.url')) {
                Log::channel('slack')->error('Driver payout approval failed', [
                    'driver_id' => $id,
                    'reason' => $e->getMessage(),
                ]);
            }
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('APPROVE DRIVER PAYOUT: Error Encountered: ' . $e->getMessage());
            if (config('logging.channels.slack.url')) {
                Log::channel('slack')->error('Driver payout approval failed', [
                    'driver_id' => $id,
                    'reason' => $e->getMessage(),
                ]);
            }
            return ShopittPlus::response(false, 'Failed to approve payout', 500);
        }
    }

    public function export(Request $request): Response
    {
        $payouts = $this->driverPayoutService->exportPayouts($request);

        $headers = [
            'Driver Name',
            'Driver Email',
            'Driver Phone',
            'Amount',
            'Currency',
            'Status',
            'Reference',
            'Paid At',
            'Created At',
        ];

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        foreach ($payouts as $payout) {
            fputcsv($handle, [
                $payout->driver?->name,
                $payout->driver?->email,
                $payout->driver?->phone,
                $payout->amount->getAmount()->toFloat(),
                $payout->amount->getCurrency()->getCurrencyCode(),
                $payout->status,
                $payout->reference,
                $payout->paid_at,
                $payout->created_at,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $filename = 'driver_payouts_' . now()->format('Y_m_d') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function reconcile(): JsonResponse
    {
        try {
            $summary = $this->driverPayoutService->reconcile();

            return ShopittPlus::response(true, 'Payout reconciliation summary retrieved.', 200, $summary);
        } catch (\Exception $e) {
            Log::error('RECONCILE PAYOUTS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to reconcile payouts', 500);
        }
    }
}

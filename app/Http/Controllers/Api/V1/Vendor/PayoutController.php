<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayoutController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        $settlements = $vendor->settlements()->latest()->get();
        return ShopittPlus::response(true, 'Vendor payouts retrieved successfully', 200, [
            'data' => $settlements,
        ]);
    }

    public function withdraw(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'amount' => ['required', 'numeric', 'min:1'],
            ]);
            $vendor = $request->user()->vendor;
            $wallet = $vendor->wallet;
            if ($wallet->balance->getAmount()->toFloat() < $data['amount']) {
                return ShopittPlus::response(false, 'Insufficient balance', 400);
            }
            // Create settlement record
            $settlement = $vendor->settlements()->create([
                'vendor_amount' => $data['amount'],
                'status' => 'PENDING',
                'settled_at' => null,
            ]);
            // Deduct from wallet
            $wallet->debit($data['amount']);
            // Optionally notify vendor
            $vendor->user->notify(new \App\Modules\Transaction\Notifications\WithdrawalSuccessfulNotification($settlement));
            return ShopittPlus::response(true, 'Payout request received', 202, [
                'amount' => $data['amount'],
                'status' => 'PENDING',
            ]);
        } catch (\Exception $e) {
            Log::error('VENDOR PAYOUT REQUEST: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to request payout', 500);
        }
    }
}

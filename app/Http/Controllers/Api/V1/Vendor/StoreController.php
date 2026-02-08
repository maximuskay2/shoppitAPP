<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StoreController extends Controller
{
    public function updateStatus(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'is_open' => ['required', 'boolean'],
            ]);

            $vendor = $request->user()->vendor;
            $cacheKey = 'vendor:store:manual_open:' . $vendor->id;
            Cache::put($cacheKey, $data['is_open'], now()->addHours(12));

            return ShopittPlus::response(true, 'Store status updated successfully', 200, [
                'is_open' => $data['is_open'],
                'source' => 'manual_override',
            ]);
        } catch (\Exception $e) {
            Log::error('VENDOR STORE STATUS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update store status', 500);
        }
    }

    public function updateHours(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'opening_time' => ['required', 'date_format:H:i'],
                'closing_time' => ['required', 'date_format:H:i'],
            ]);

            $vendor = $request->user()->vendor;
            $vendor->update([
                'opening_time' => $data['opening_time'],
                'closing_time' => $data['closing_time'],
            ]);

            return ShopittPlus::response(true, 'Store hours updated successfully', 200, [
                'opening_time' => $vendor->opening_time?->format('H:i'),
                'closing_time' => $vendor->closing_time?->format('H:i'),
            ]);
        } catch (\Exception $e) {
            Log::error('VENDOR STORE HOURS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update store hours', 500);
        }
    }
}

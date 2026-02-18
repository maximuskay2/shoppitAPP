<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CouponController extends Controller
{
    public function index(): JsonResponse
    {
        // TODO: Replace with real DB fetch
        $coupons = [
            ['id' => 1, 'code' => 'WELCOME10', 'discount' => 10, 'active' => true],
            ['id' => 2, 'code' => 'FREESHIP', 'discount' => 0, 'active' => false],
        ];
        return ShopittPlus::response(true, 'Coupons retrieved', 200, $coupons);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:32',
            'discount' => 'required|numeric|min:0',
            'active' => 'required|boolean',
        ]);
        // TODO: Save to DB
        Log::info('Coupon created', $data);
        return ShopittPlus::response(true, 'Coupon created', 201, $data);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'code' => 'sometimes|string|max:32',
            'discount' => 'sometimes|numeric|min:0',
            'active' => 'sometimes|boolean',
        ]);
        // TODO: Update in DB
        Log::info('Coupon updated', ['id' => $id] + $data);
        return ShopittPlus::response(true, 'Coupon updated', 200, ['id' => $id] + $data);
    }

    public function destroy($id): JsonResponse
    {
        // TODO: Delete from DB
        Log::info('Coupon deleted', ['id' => $id]);
        return ShopittPlus::response(true, 'Coupon deleted', 200);
    }
}

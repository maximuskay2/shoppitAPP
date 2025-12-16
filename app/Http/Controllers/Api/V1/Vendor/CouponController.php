<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vendor\StoreCouponRequest;
use App\Http\Requests\Api\V1\Vendor\UpdateCouponRequest;
use App\Http\Resources\Commerce\CouponResource;
use App\Modules\Commerce\Models\Coupon;
use App\Modules\Commerce\Models\Settings;
use App\Modules\Commerce\Services\CouponService;
use Brick\Money\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CouponController extends Controller
{
    public function __construct(private readonly CouponService $couponService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $vendor = $user->vendor;

            $coupons = $this->couponService->index($vendor);

            return ShopittPlus::response(true, 'Coupons retrieved successfully', 200, CouponResource::collection($coupons));
        } catch (InvalidArgumentException $e) {
            Log::error('GET COUPONS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('GET COUPONS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve coupons', 500);
        }
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $vendor = $user->vendor;

            $coupon = $this->couponService->store($vendor, $request->validated());
            return ShopittPlus::response(true, 'Coupon created successfully', 201, new CouponResource($coupon));
        } catch (InvalidArgumentException $e) {
            Log::error('CREATE COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('CREATE COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create coupon', 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $vendor = $user->vendor;

            $coupon = $this->couponService->show($vendor, $id);
            return ShopittPlus::response(true, 'Coupon retrieved successfully', 200, new CouponResource($coupon));
        } catch (InvalidArgumentException $e) {
            Log::error('GET COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 403);
        } catch (\Exception $e) {
            Log::error('GET COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve coupon', 500);
        }
    }

    public function update(UpdateCouponRequest $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $vendor = $user->vendor;

            $coupon = $this->couponService->update($vendor, $id, $request->validated());
            return ShopittPlus::response(true, 'Coupon updated successfully', 200, new CouponResource($coupon));
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 403);
        } catch (\Exception $e) {
            Log::error('UPDATE COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update coupon', 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $vendor = $user->vendor;

            $this->couponService->delete($vendor, $id);
            return ShopittPlus::response(true, 'Coupon deleted successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('DELETE COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 403);
        } catch (\Exception $e) {
            Log::error('DELETE COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete coupon', 500);
        }
    }
}
<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vendor\StoreCouponRequest;
use App\Http\Requests\Api\V1\Vendor\UpdateCouponRequest;
use App\Http\Resources\Commerce\CouponResource;
use App\Modules\Commerce\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CouponController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $vendor = $user->vendor;

            if (!$vendor) {
                throw new InvalidArgumentException('User is not a vendor');
            }

            $query = Coupon::where('vendor_id', $vendor->id);

            // Filter by active status if provided
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $coupons = $query->latest()->paginate(20);

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
            DB::beginTransaction();

            $user = Auth::user();
            $vendor = $user->vendor;

            if (!$vendor) {
                throw new InvalidArgumentException('User is not a vendor');
            }

            $validatedData = $request->validated();

            // Generate unique code if not provided
            if (empty($validatedData['code'])) {
                $validatedData['code'] = $this->generateUniqueCode();
            }

            $coupon = Coupon::create([
                'vendor_id' => $vendor->id,
                'code' => strtoupper($validatedData['code']),
                'discount_type' => $validatedData['discount_type'],
                'discount_amount' => $validatedData['discount_amount'] ?? null,
                'percent' => $validatedData['percent'] ?? null,
                'minimum_order_value' => $validatedData['minimum_order_value'] ?? 0,
                'maximum_discount' => $validatedData['maximum_discount'] ?? null,
                'usage_per_customer' => $validatedData['usage_per_customer'] ?? 1,
                'is_visible' => $validatedData['is_visible'] ?? true,
                'is_active' => $validatedData['is_active'] ?? true,
            ]);

            DB::commit();

            return ShopittPlus::response(true, 'Coupon created successfully', 201, new CouponResource($coupon));
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('CREATE COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CREATE COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create coupon', 500);
        }
    }

    public function show(Coupon $coupon): JsonResponse
    {
        try {
            $user = Auth::user();
            $vendor = $user->vendor;

            if (!$vendor || $coupon->vendor_id !== $vendor->id) {
                throw new InvalidArgumentException('Unauthorized access to coupon');
            }

            return ShopittPlus::response(true, 'Coupon retrieved successfully', 200, new CouponResource($coupon));
        } catch (InvalidArgumentException $e) {
            Log::error('GET COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 403);
        } catch (\Exception $e) {
            Log::error('GET COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve coupon', 500);
        }
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $vendor = $user->vendor;

            if (!$vendor || $coupon->vendor_id !== $vendor->id) {
                throw new InvalidArgumentException('Unauthorized access to coupon');
            }

            $validatedData = $request->validated();

            $coupon->update([
                'code' => isset($validatedData['code']) ? strtoupper($validatedData['code']) : $coupon->code,
                'discount_type' => $validatedData['discount_type'] ?? $coupon->discount_type,
                'discount_amount' => $validatedData['discount_amount'] ?? $coupon->discount_amount,
                'percent' => $validatedData['percent'] ?? $coupon->percent,
                'minimum_order_value' => $validatedData['minimum_order_value'] ?? $coupon->minimum_order_value,
                'maximum_discount' => $validatedData['maximum_discount'] ?? $coupon->maximum_discount,
                'usage_per_customer' => $validatedData['usage_per_customer'] ?? $coupon->usage_per_customer,
                'is_visible' => $validatedData['is_visible'] ?? $coupon->is_visible,
                'is_active' => $validatedData['is_active'] ?? $coupon->is_active,
            ]);

            DB::commit();

            return ShopittPlus::response(true, 'Coupon updated successfully', 200, new CouponResource($coupon));
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('UPDATE COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 403);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UPDATE COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update coupon', 500);
        }
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $vendor = $user->vendor;

            if (!$vendor || $coupon->vendor_id !== $vendor->id) {
                throw new InvalidArgumentException('Unauthorized access to coupon');
            }

            $coupon->delete();

            DB::commit();

            return ShopittPlus::response(true, 'Coupon deleted successfully', 200);
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('DELETE COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 403);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DELETE COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete coupon', 500);
        }
    }

    public function toggleVisibility(Coupon $coupon): JsonResponse
    {
        try {
            $user = Auth::user();
            $vendor = $user->vendor;

            if (!$vendor || $coupon->vendor_id !== $vendor->id) {
                throw new InvalidArgumentException('Unauthorized access to coupon');
            }

            $coupon->update(['is_visible' => !$coupon->is_visible]);

            return ShopittPlus::response(true, 'Coupon visibility updated successfully', 200, new CouponResource($coupon));
        } catch (InvalidArgumentException $e) {
            Log::error('TOGGLE COUPON VISIBILITY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 403);
        } catch (\Exception $e) {
            Log::error('TOGGLE COUPON VISIBILITY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update coupon visibility', 500);
        }
    }

    public function toggleActive(Coupon $coupon): JsonResponse
    {
        try {
            $user = Auth::user();
            $vendor = $user->vendor;

            if (!$vendor || $coupon->vendor_id !== $vendor->id) {
                throw new InvalidArgumentException('Unauthorized access to coupon');
            }

            $coupon->update(['is_active' => !$coupon->is_active]);

            return ShopittPlus::response(true, 'Coupon status updated successfully', 200, new CouponResource($coupon));
        } catch (InvalidArgumentException $e) {
            Log::error('TOGGLE COUPON ACTIVE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 403);
        } catch (\Exception $e) {
            Log::error('TOGGLE COUPON ACTIVE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update coupon status', 500);
        }
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'COUPON' . strtoupper(substr(md5(uniqid()), 0, 6));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }
}
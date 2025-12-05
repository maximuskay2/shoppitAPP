<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vendor\UpdateOrderStatusRequest;
use App\Http\Resources\Commerce\OrderResource;
use App\Modules\Commerce\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $vendor = $user->vendor;

            if (!$vendor) {
                throw new InvalidArgumentException('User is not a vendor');
            }

            $query = Order::where('vendor_id', $vendor->id);

            // Filter by status if provided
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            $orders = $query->with(['lineItems.product', 'user'])->latest()->paginate(20);

            return ShopittPlus::response(true, 'Orders retrieved successfully', 200, OrderResource::collection($orders));
        } catch (InvalidArgumentException $e) {
            Log::error('GET VENDOR ORDERS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('GET VENDOR ORDERS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve orders', 500);
        }
    }

    public function show($orderId): JsonResponse
    {
        try {
            $user = Auth::user();
            $vendor = $user->vendor;

            if (!$vendor) {
                throw new InvalidArgumentException('User is not a vendor');
            }

            $order = Order::where('vendor_id', $vendor->id)
                ->where('id', $orderId)
                ->with(['lineItems.product', 'user'])
                ->firstOrFail();

            return ShopittPlus::response(true, 'Order retrieved successfully', 200, new OrderResource($order));
        } catch (InvalidArgumentException $e) {
            Log::error('GET VENDOR ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('GET VENDOR ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve order', 500);
        }
    }

    public function updateStatus(UpdateOrderStatusRequest $request, $orderId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $vendor = $user->vendor;

            if (!$vendor) {
                throw new InvalidArgumentException('User is not a vendor');
            }

            $order = Order::where('vendor_id', $vendor->id)
                ->where('id', $orderId)
                ->firstOrFail();

            $validatedData = $request->validated();
            $newStatus = $validatedData['status'];

            // Update status and set appropriate timestamps
            $order->status = $newStatus;

            if ($newStatus === 'shipped' && !$order->dispatched_at) {
                $order->dispatched_at = now();
            } elseif ($newStatus === 'delivered' && !$order->completed_at) {
                $order->completed_at = now();
            }

            $order->save();

            DB::commit();

            return ShopittPlus::response(true, 'Order status updated successfully', 200, new OrderResource($order));
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('UPDATE ORDER STATUS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UPDATE ORDER STATUS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update order status', 500);
        }
    }
}
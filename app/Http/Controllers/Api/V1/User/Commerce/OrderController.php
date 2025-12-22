<?php

namespace App\Http\Controllers\Api\V1\User\Commerce;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Commerce\OrderResource;
use App\Modules\Commerce\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Order::where('user_id', $user->id);

            // Filter by status if provided
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            $orders = $query->with(['lineItems.product', 'vendor', 'user'])->latest()->paginate(20);

            return ShopittPlus::response(true, 'Orders retrieved successfully', 200, OrderResource::collection($orders));
        } catch (InvalidArgumentException $e) {
            Log::error('GET ORDERS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('GET ORDERS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve orders', 500);
        }
    }

    public function show($orderId): JsonResponse
    {
        try {
            $user = Auth::user();
            $order = Order::where('user_id', $user->id)
                ->where('id', $orderId)
                ->with(['lineItems.product', 'vendor', 'user'])
                ->firstOrFail();

            return ShopittPlus::response(true, 'Order retrieved successfully', 200, new OrderResource($order));
        } catch (InvalidArgumentException $e) {
            Log::error('GET ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('GET ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve order', 500);
        }
    }
}
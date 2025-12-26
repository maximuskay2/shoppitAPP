<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vendor\UpdateOrderStatusRequest;
use App\Http\Resources\Commerce\OrderResource;
use App\Http\Resources\Commerce\SettlementResource;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Services\Vendor\OrderService;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}
    
    public function index(Request $request): JsonResponse
    {
        try {
            $user = User::find(Auth::id());
            $vendor = $user->vendor;

            $orders = $this->orderService->index($vendor, $request);

            $data = [
                'data' => OrderResource::collection($orders->items()),
                'next_cursor' => $orders->nextCursor()?->encode(),
                'prev_cursor' => $orders->previousCursor()?->encode(),
                'has_more' => $orders->hasMorePages(),
                'per_page' => $orders->perPage(),
            ];
            return ShopittPlus::response(true, 'Orders retrieved successfully', 200, $data);
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
            $user = User::find(Auth::id());
            $vendor = $user->vendor;

            $order = $this->orderService->getOrderById($vendor, $orderId);
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
            $user = User::find(Auth::id());
            $vendor = $user->vendor;            

            $this->orderService->updateStatus($vendor, $orderId, $request->validated());
            return ShopittPlus::response(true, 'Order status updated successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE ORDER STATUS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('UPDATE ORDER STATUS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update order status', 500);
        }
    }

    public function settlements(): JsonResponse
    {
        try {
            $user = User::find(Auth::id());
            $vendor = $user->vendor;            

            $settlements = $this->orderService->settlements($vendor);
            $data = [
                'data' => SettlementResource::collection($settlements->items()),
                'next_cursor' => $settlements->nextCursor()?->encode(),
                'prev_cursor' => $settlements->previousCursor()?->encode(),
                'has_more' => $settlements->hasMorePages(),
                'per_page' => $settlements->perPage(),
            ];
            return ShopittPlus::response(true, 'Settlements retrieved successfully', 200, $data);
        } catch (InvalidArgumentException $e) {
            Log::error('SETTLEMENTS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('SETTLEMENTS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve settlements', 500);
        }
    }

    public function orderStatisticsSummary(Request $request): JsonResponse
    {
        try {
            $user = User::find(Auth::id());
            $vendor = $user->vendor;            

            $statistics = $this->orderService->orderStatisticsSummary($vendor, $request);
            return ShopittPlus::response(true, 'Order statistics retrieved successfully', 200, $statistics);
        } catch (InvalidArgumentException $e) {
            Log::error('ORDER STATISTICS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('ORDER STATISTICS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve order statistics', 500);
        }
    }
}
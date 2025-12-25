<?php

namespace App\Http\Controllers\Api\V1\User\Commerce;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Commerce\OrderResource;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Services\OrderService;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $user = User::find(Auth::id());

            $orders = $this->orderService->index($user, $request);

            $data = [
                'data' => OrderResource::collection($orders->items()),
                'next_cursor' => $orders->nextCursor()?->encode(),
                'prev_cursor' => $orders->previousCursor()?->encode(),
                'has_more' => $orders->hasMorePages(),
                'per_page' => $orders->perPage(),
            ];
            return ShopittPlus::response(true, 'Orders retrieved successfully', 200, $data);
        } catch (InvalidArgumentException $e) {
            Log::error('GET ORDERS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('GET ORDERS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve orders', 500);
        }
    }

    public function show(string $orderId): JsonResponse
    {
        try {
            $user = User::find(Auth::id());
            
            $order = $this->orderService->getOrderById($user, $orderId);
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
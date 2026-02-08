<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\DriverDeliverRequest;
use App\Http\Requests\Api\V1\Driver\DriverCancelOrderRequest;
use App\Http\Requests\Api\V1\Driver\DriverRejectRequest;
use App\Http\Resources\Commerce\OrderResource;
use App\Modules\Commerce\Services\Driver\DriverOrderService;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function __construct(private readonly DriverOrderService $driverOrderService) {}

    public function available(Request $request): JsonResponse
    {
        try {
            $orders = $this->driverOrderService->availableOrders($request);

            $data = [
                'data' => OrderResource::collection($orders->items()),
                'next_cursor' => $orders->nextCursor()?->encode(),
                'prev_cursor' => $orders->previousCursor()?->encode(),
                'has_more' => $orders->hasMorePages(),
                'per_page' => $orders->perPage(),
            ];

            return ShopittPlus::response(true, 'Available orders retrieved successfully', 200, $data);
        } catch (InvalidArgumentException $e) {
            Log::error('DRIVER AVAILABLE ORDERS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DRIVER AVAILABLE ORDERS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve available orders', 500);
        }
    }

    public function accept(string $orderId): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            $order = $this->driverOrderService->acceptOrder($driver, $orderId);

            return ShopittPlus::response(true, 'Order accepted successfully', 200, new OrderResource($order));
        } catch (InvalidArgumentException $e) {
            Log::error('DRIVER ACCEPT ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DRIVER ACCEPT ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to accept order', 500);
        }
    }

    public function reject(DriverRejectRequest $request, string $orderId): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            $order = $this->driverOrderService->rejectOrder(
                $driver,
                $orderId,
                $request->input('reason')
            );

            return ShopittPlus::response(true, 'Order rejected successfully', 200, new OrderResource($order));
        } catch (InvalidArgumentException $e) {
            Log::error('DRIVER REJECT ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DRIVER REJECT ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to reject order', 500);
        }
    }

    public function pickup(string $orderId): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            $order = $this->driverOrderService->markPickedUp($driver, $orderId);

            return ShopittPlus::response(true, 'Order marked as picked up', 200, new OrderResource($order));
        } catch (InvalidArgumentException $e) {
            Log::error('DRIVER PICKUP ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DRIVER PICKUP ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update pickup status', 500);
        }
    }

    public function startDelivery(string $orderId): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            $order = $this->driverOrderService->startDelivery($driver, $orderId);

            return ShopittPlus::response(true, 'Order is out for delivery', 200, new OrderResource($order));
        } catch (InvalidArgumentException $e) {
            Log::error('DRIVER START DELIVERY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DRIVER START DELIVERY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to start delivery', 500);
        }
    }

    public function deliver(DriverDeliverRequest $request, string $orderId): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            $order = $this->driverOrderService->deliverOrder($driver, $orderId, $request->input('otp_code'));

            return ShopittPlus::response(true, 'Order delivered successfully', 200, new OrderResource($order));
        } catch (InvalidArgumentException $e) {
            Log::error('DRIVER DELIVER ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DRIVER DELIVER ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to deliver order', 500);
        }
    }

    public function cancel(DriverCancelOrderRequest $request, string $orderId): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            $order = $this->driverOrderService->cancelOrder(
                $driver,
                $orderId,
                $request->input('reason')
            );

            return ShopittPlus::response(true, 'Order cancelled successfully', 200, new OrderResource($order));
        } catch (InvalidArgumentException $e) {
            Log::error('DRIVER CANCEL ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DRIVER CANCEL ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to cancel order', 500);
        }
    }

    public function active(): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            $order = $this->driverOrderService->activeOrder($driver);

            $data = $order ? new OrderResource($order) : null;

            return ShopittPlus::response(true, 'Active order retrieved successfully', 200, $data);
        } catch (InvalidArgumentException $e) {
            Log::error('DRIVER ACTIVE ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DRIVER ACTIVE ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve active order', 500);
        }
    }

    public function history(Request $request): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            $orders = $this->driverOrderService->orderHistory($driver, $request);

            $data = [
                'data' => OrderResource::collection($orders->items()),
                'next_cursor' => $orders->nextCursor()?->encode(),
                'prev_cursor' => $orders->previousCursor()?->encode(),
                'has_more' => $orders->hasMorePages(),
                'per_page' => $orders->perPage(),
            ];

            return ShopittPlus::response(true, 'Order history retrieved successfully', 200, $data);
        } catch (InvalidArgumentException $e) {
            Log::error('DRIVER ORDER HISTORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DRIVER ORDER HISTORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve order history', 500);
        }
    }
}

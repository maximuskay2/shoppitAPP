<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vendor\UpdateOrderStatusRequest;
use App\Modules\Commerce\Services\Admin\OrderManagementService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class OrderManagementController extends Controller
{
    public function __construct(private readonly OrderManagementService $orderManagementService) {}

    /**
     * Get list of orders with advanced filtering and sorting
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $orders = $this->orderManagementService->listOrders($request);

            return ShopittPlus::response(true, 'Orders retrieved successfully', 200, $orders);
        } catch (InvalidArgumentException $e) {
            Log::error('LIST ORDERS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('LIST ORDERS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve orders', 500);
        }
    }

    /**
     * Get order statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $stats = $this->orderManagementService->getOrderStats($request);

            return ShopittPlus::response(true, 'Order statistics retrieved successfully', 200, $stats);
        } catch (Exception $e) {
            Log::error('GET ORDER STATS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve order statistics', 500);
        }
    }

    /**
     * Get order details
     */
    public function show(string $id): JsonResponse
    {
        try {
            $order = $this->orderManagementService->getOrderDetails($id);

            return ShopittPlus::response(true, 'Order details retrieved successfully', 200, $order);
        } catch (InvalidArgumentException $e) {
            Log::error('GET ORDER DETAILS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('GET ORDER DETAILS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve order details', 500);
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(UpdateOrderStatusRequest $request, string $id): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $order = $this->orderManagementService->updateOrderStatus($id, $validatedData);

            return ShopittPlus::response(true, 'Order status updated successfully', 200, $order);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE ORDER STATUS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPDATE ORDER STATUS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update order status', 500);
        }
    }
}
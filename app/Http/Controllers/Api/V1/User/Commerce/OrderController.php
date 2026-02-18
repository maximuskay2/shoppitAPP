<?php

namespace App\Http\Controllers\Api\V1\User\Commerce;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vendor\UpdateOrderStatusRequest;
use App\Http\Resources\Commerce\OrderResource;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Review;
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

    public function updateStatus(UpdateOrderStatusRequest $request, $orderId): JsonResponse
    {
        try {
            $user = User::find(Auth::id());

            $this->orderService->updateStatus($user, $orderId, $request->validated());
            return ShopittPlus::response(true, 'Order status updated successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE ORDER STATUS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('UPDATE ORDER STATUS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update order status', 500);
        }
    }

    /**
     * Cancel an order for the customer
     */
    public function cancel(string $orderId, Request $request): JsonResponse
    {
        try {
            $user = User::find(Auth::id());
            $order = $this->orderService->getOrderById($user, $orderId);
            
            if (!in_array($order->status, ['PENDING', 'PROCESSING', 'CONFIRMED'])) {
                return ShopittPlus::response(false, 'Order cannot be cancelled at this stage.', 400);
            }
            
            $reason = $request->input('reason', 'Customer requested cancellation');
            $this->orderService->markOrderAsCancelled($order, $reason);
            
            return ShopittPlus::response(true, 'Order cancelled successfully.', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('CANCEL ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('CANCEL ORDER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to cancel order', 500);
        }
    }

    /**
     * Request a refund for an order
     */
    public function refundRequest(string $orderId, Request $request): JsonResponse
    {
        try {
            $user = User::find(Auth::id());
            $order = $this->orderService->getOrderById($user, $orderId);
            
            if (!in_array($order->status, ['DELIVERED', 'COMPLETED'])) {
                return ShopittPlus::response(false, 'Refund can only be requested for delivered or completed orders.', 400);
            }
            
            $order->refund_status = 'REQUESTED';
            $order->refund_reason = $request->input('reason');
            $order->refund_requested_at = now();
            $order->save();
            
            return ShopittPlus::response(true, 'Refund requested successfully.', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('REFUND REQUEST: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('REFUND REQUEST: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to request refund', 500);
        }
    }

    /**
     * Get refund status for an order
     */
    public function refundStatus(string $orderId): JsonResponse
    {
        try {
            $user = User::find(Auth::id());
            $order = $this->orderService->getOrderById($user, $orderId);
            
            return ShopittPlus::response(true, 'Refund status retrieved.', 200, [
                'refund_status' => $order->refund_status ?? 'NONE',
                'refund_reason' => $order->refund_reason,
                'refund_requested_at' => $order->refund_requested_at,
                'refund_processed_at' => $order->refund_processed_at,
            ]);
        } catch (InvalidArgumentException $e) {
            Log::error('REFUND STATUS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('REFUND STATUS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to get refund status', 500);
        }
    }

    /**
     * Rate the driver for an order
     */
    public function rateDriver(string $orderId, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:500',
            ]);

            $user = User::find(Auth::id());
            $order = $this->orderService->getOrderById($user, $orderId);
            
            if ($order->status !== 'DELIVERED' && $order->status !== 'COMPLETED') {
                return ShopittPlus::response(false, 'Can only rate driver for delivered orders.', 400);
            }
            
            if (!$order->driver_id) {
                return ShopittPlus::response(false, 'No driver assigned to this order.', 400);
            }
            
            // Check if already rated
            $existingReview = Review::where('order_id', $order->id)
                ->where('user_id', $user->id)
                ->where('reviewable_type', 'driver')
                ->first();
                
            if ($existingReview) {
                return ShopittPlus::response(false, 'You have already rated the driver for this order.', 400);
            }
            
            // Create driver review
            $review = Review::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'reviewable_id' => $order->driver_id,
                'reviewable_type' => 'driver',
                'rating' => $request->input('rating'),
                'comment' => $request->input('comment'),
            ]);
            
            // Update order
            $order->driver_rating = $request->input('rating');
            $order->driver_rated_at = now();
            $order->save();
            
            return ShopittPlus::response(true, 'Driver rated successfully.', 200, [
                'rating' => $review->rating,
                'comment' => $review->comment,
            ]);
        } catch (InvalidArgumentException $e) {
            Log::error('RATE DRIVER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('RATE DRIVER: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to rate driver', 500);
        }
    }
}

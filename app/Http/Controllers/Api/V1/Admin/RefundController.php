<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Commerce\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RefundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Order::query()
                ->whereNotNull('refund_status')
                ->with(['user', 'vendor.user'])
                ->latest();

            if ($status = $request->input('status')) {
                $query->where('refund_status', $status);
            }

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($user) use ($search) {
                            $user->where('email', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            }

            $perPage = min((int) $request->input('per_page', 20), 100);
            $refunds = $query->paginate($perPage);

            return ShopittPlus::response(true, 'Refund requests retrieved successfully', 200, $refunds);
        } catch (\Exception $e) {
            Log::error('REFUND LIST: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve refunds', 500);
        }
    }

    public function approve($id): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);
            $order->update([
                'refund_status' => 'APPROVED',
                'refund_processed_at' => now(),
                'status' => 'REFUNDED',
            ]);

            Log::info('Refund approved', ['id' => $id]);
            return ShopittPlus::response(true, 'Refund approved', 200, $order);
        } catch (\Exception $e) {
            Log::error('REFUND APPROVE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to approve refund', 500);
        }
    }

    public function reject(Request $request, $id): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);
            $order->update([
                'refund_status' => 'REJECTED',
                'refund_reason' => $request->input('reason'),
                'refund_processed_at' => now(),
            ]);

            Log::info('Refund rejected', ['id' => $id]);
            return ShopittPlus::response(true, 'Refund rejected', 200, $order);
        } catch (\Exception $e) {
            Log::error('REFUND REJECT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to reject refund', 500);
        }
    }
}

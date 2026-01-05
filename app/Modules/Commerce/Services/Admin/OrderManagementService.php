<?php

namespace App\Modules\Commerce\Services\Admin;

use App\Modules\Commerce\Events\OrderCancelled;
use App\Modules\Commerce\Events\OrderCompleted;
use App\Modules\Commerce\Events\OrderDispatched;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Settlement;

class OrderManagementService
{
    public function listOrders($request)
    {
        $query = Order::query()->latest()->with(['user', 'vendor.user']);

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Date range filter
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Search filter (customer or vendor name)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                // Search in customer (user) details
                ->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                })
                // Search in vendor details
                ->orWhereHas('vendor.user', function ($vendorUserQuery) use ($search) {
                    $vendorUserQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('vendor', function ($vendorQuery) use ($search) {
                    $vendorQuery->where('business_name', 'like', "%{$search}%");
                });
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortFields = ['created_at', 'status', 'total_amount', 'updated_at'];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $perPage = min($perPage, 100); // Max 100 items per page

        return $query->paginate($perPage);
    }

    public function getOrderStats($request)
    {
        // Basic order counts
        $totalOrders = Order::count();
        $totalOrdersToday = Order::whereDate('created_at', today())->count();
        $totalCompletedOrders = Order::where('status', 'COMPLETED')->count();
        
        // Total revenue from platform fees in settlements
        $settlements = Settlement::where('status', 'SUCCESSFUL')->get();
        $totalRevenue = $settlements->sum(function ($settlement) {
            return $settlement->platform_fee->getAmount()->toFloat();
        });
        
        // Order status breakdown (all time) - include all statuses even with 0 count
        $allStatuses = ['PENDING', 'PAID', 'PROCESSING', 'DISPATCHED', 'COMPLETED', 'CANCELLED', 'REFUNDED'];
        $totalOrdersForPercentage = Order::count();
        $statusBreakdown = [];
        
        // Get actual counts from database
        $statusCounts = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        // Include all statuses with their counts (0 if not present)
        foreach ($allStatuses as $status) {
            $count = $statusCounts[$status] ?? 0;
            $percentage = $totalOrdersForPercentage > 0 
                ? round(($count / $totalOrdersForPercentage) * 100, 2) 
                : 0;
            
            $statusBreakdown[] = [
                'status' => $status,
                'count' => $count,
                'percentage' => $percentage,
            ];
        }
        
        // Daily sales for the specified or current month
        $targetDate = now();
        
        if ($request->has('month') && $request->has('year')) {
            $targetDate = now()->setYear(intval($request->input('year')))->setMonth(intval($request->input('month')));
        } elseif ($request->has('month')) {
            $targetDate = now()->setMonth(intval($request->input('month')));
        } elseif ($request->has('year')) {
            $targetDate = now()->setYear(intval($request->input('year')));
        }
        
        $startOfMonth = $targetDate->copy()->startOfMonth();
        $endOfMonth = $targetDate->copy()->endOfMonth();
        
        // Determine the end date: current date if in current month, otherwise end of month
        $isCurrentMonth = $targetDate->isSameMonth(now());
        $endDate = $isCurrentMonth ? now() : $endOfMonth;
        
        // Get daily sales data from database
        $dailySalesFromDB = Order::selectRaw('DATE(created_at) as date, SUM(CAST(gross_total_amount AS DECIMAL(20,2))) as total_sales')
            ->whereBetween('created_at', [$startOfMonth, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total_sales', 'date')
            ->toArray();
        
        // Create complete daily sales data including days with 0 sales
        $salesData = [];
        $currentDay = $startOfMonth->copy();
        
        while ($currentDay->lte($endDate)) {
            $dateString = $currentDay->toDateString();
            $sales = isset($dailySalesFromDB[$dateString]) ? floatval($dailySalesFromDB[$dateString]) : 0;
            
            $salesData[] = [
                'day' => $currentDay->day,
                'date' => $dateString,
                'sales' => $sales,
            ];
            
            $currentDay->addDay();
        }

        return [
            'total_orders' => $totalOrders,
            'total_orders_today' => $totalOrdersToday,
            'total_completed_orders' => $totalCompletedOrders,
            'total_revenue' => $totalRevenue,
            'status_breakdown' => $statusBreakdown,
            'daily_sales_data' => $salesData,
        ];
    }

    public function getOrderDetails(string $orderId): Order
    {
        $order = Order::with(['lineItems.product', 'vendor.user', 'user'])->find($orderId);

        if (!$order) {
            throw new \InvalidArgumentException("OrderManagementService.getOrderDetails(): Order not found for ID: $orderId.");
        }

        return $order;
    }

    public function updateOrderStatus(string $orderId, array $data): Order
    {
        $order = $this->getOrderDetails($orderId);

        $order->status = $data['status'];
        $order->save();

        if ($data['status'] === 'COMPLETED') {
            event(new OrderCompleted($order));
        }

        if ($data['status'] === 'DISPATCHED') {
            event(new OrderDispatched($order));
        }

        if ($data['status'] === 'CANCELLED') {
            event(new OrderCancelled($order));
        }

        return $order;
    }
}
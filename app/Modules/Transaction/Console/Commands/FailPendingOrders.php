<?php

namespace App\Modules\Transaction\Console\Commands;

use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Services\OrderService;
use Illuminate\Console\Command;

class FailPendingOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fail-pending-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to fail pending orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orders = Order::where('status', 'PENDING')
            ->where('created_at', '<=', now()->subMinutes(5))
            ->get();

        foreach ($orders as $order) {
            try {
                $orderService = resolve(OrderService::class);
                $orderService->updateOrderStatus($order, 'FAILED');
            }
            catch (\Exception $e) {
                // Log the error but continue processing other transactions
                continue;
            }
        }
    }
}

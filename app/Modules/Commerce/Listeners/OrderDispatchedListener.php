<?php

namespace App\Modules\Commerce\Listeners;

use App\Modules\Commerce\Events\OrderDispatched;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Notifications\OrderDispatchedNotification;
use App\Modules\Commerce\Services\OrderService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderDispatchedListener implements ShouldQueue
{
    public function __construct(
        public OrderService $orderService,
    ) {}

    public function handle(OrderDispatched $event): void
    {
        $order = $event->order;

        Log::info('OrderDispatchedListener.handle() :' . json_encode($event));

        Cache::lock("order:{$order->id}", 10)->block(5, function () use (
            $order,
        ) {
            try {
                DB::beginTransaction();

                $this->orderService->markOrderAsDispatched($order);
                DB::commit();

                $order = Order::find($order->id)->load('lineItems.product', 'user', 'vendor');
                $order->user->notify(new OrderDispatchedNotification($order));
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("OrderDispatchedListener.handle() - Error Encountered - " . $e->getMessage());
                if (config('logging.channels.slack.url')) {
                    Log::channel('slack')->error('Order dispatch failed', [
                        'order_id' => $order->id,
                        'status' => $order->status,
                        'message' => $e->getMessage(),
                    ]);
                }
                throw $e;
            }
        });
    }
}

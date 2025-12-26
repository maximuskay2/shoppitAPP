<?php

namespace App\Modules\Commerce\Listeners;

use App\Modules\Commerce\Events\OrderPaymentSuccessful;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Notifications\OrderPlacedSuccessfullyNotification;
use App\Modules\Commerce\Notifications\OrderReceivedNotification;
use App\Modules\Commerce\Services\OrderService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderPaymentSuccessfulListener implements ShouldQueue
{
    public function __construct(
        public OrderService $orderService,
    ) {}

    public function handle(OrderPaymentSuccessful $event): void
    {
        $order = $event->order;

        Log::info('OrderPaymentSuccessfulListener.handle() :' . json_encode($event));

        Cache::lock("order:{$order->id}", 10)->block(5, function () use (
            $order,
        ) {
            try {
                DB::beginTransaction();

                $this->orderService->markOrderAsPaid($order);
                DB::commit();

                $order = Order::find($order->id)->load('lineItems.product', 'user', 'vendor');
                $order->user->notify(new OrderPlacedSuccessfullyNotification($order));
                $order->vendor->user->notify(new OrderReceivedNotification($order));
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("OrderPaymentSuccessfulListener.handle() - Error Encountered - " . $e->getMessage());
                throw $e;
            }
        });
    }
}

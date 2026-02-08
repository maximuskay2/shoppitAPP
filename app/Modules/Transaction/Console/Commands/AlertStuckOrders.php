<?php

namespace App\Modules\Transaction\Console\Commands;

use App\Modules\Commerce\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AlertStuckOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:alert-stuck-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Alert when orders remain READY_FOR_PICKUP for too long';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $threshold = now()->subMinutes(15);
        $orders = Order::where('status', 'READY_FOR_PICKUP')
            ->where('created_at', '<=', $threshold)
            ->get();

        Cache::put('alerts:stuck_orders:last_run', now()->toISOString(), now()->addHours(2));
        Cache::put('alerts:stuck_orders:last_count', $orders->count(), now()->addHours(2));
        Cache::put('alerts:stuck_orders:last_oldest_created_at', $orders->min('created_at'), now()->addHours(2));

        if ($orders->isEmpty()) {
            return Command::SUCCESS;
        }

        $cooldownKey = 'alerts:stuck_orders:last_alert_at';
        if (Cache::has($cooldownKey)) {
            return Command::SUCCESS;
        }

        Cache::put($cooldownKey, now()->toISOString(), now()->addMinutes(15));

        if (config('logging.channels.slack.url')) {
            $sampleIds = $orders->take(5)->pluck('id')->toArray();
            Log::channel('slack')->warning('Stuck orders summary', [
                'count' => $orders->count(),
                'oldest_created_at' => $orders->min('created_at'),
                'sample_order_ids' => $sampleIds,
            ]);
        }

        return Command::SUCCESS;
    }
}

<?php

namespace App\Modules\Transaction\Console\Commands;

use App\Modules\User\Models\Driver;
use App\Modules\User\Models\DriverLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlertStaleDriverLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:alert-stale-driver-locations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Alert when online drivers stop sending location updates';

    public function handle(): int
    {
        $thresholdMinutes = (int) env('DRIVER_LOCATION_STALE_MINUTES', 10);
        $cooldownMinutes = (int) env('DRIVER_LOCATION_ALERT_COOLDOWN_MINUTES', 10);
        $threshold = now()->subMinutes($thresholdMinutes);

        $onlineUserIds = Driver::where('is_online', true)->pluck('user_id');
        Cache::put('alerts:driver_locations:last_run', now()->toISOString(), now()->addHours(2));

        if ($onlineUserIds->isEmpty()) {
            Cache::put('alerts:driver_locations:last_count', 0, now()->addHours(2));
            Cache::put('alerts:driver_locations:last_oldest_recorded_at', null, now()->addHours(2));
            return Command::SUCCESS;
        }

        $latestLocations = DriverLocation::query()
            ->select('user_id', DB::raw('MAX(recorded_at) as latest_recorded_at'))
            ->whereIn('user_id', $onlineUserIds)
            ->groupBy('user_id')
            ->get();

        $latestMap = $latestLocations->keyBy('user_id');
        $staleUserIds = $latestLocations
            ->filter(fn ($row) => $row->latest_recorded_at <= $threshold)
            ->pluck('user_id');

        $missingUserIds = $onlineUserIds->diff($latestLocations->pluck('user_id'));
        $staleCount = $staleUserIds->count() + $missingUserIds->count();

        $oldestRecorded = $latestLocations
            ->filter(fn ($row) => $row->latest_recorded_at <= $threshold)
            ->min('latest_recorded_at');

        Cache::put('alerts:driver_locations:last_count', $staleCount, now()->addHours(2));
        Cache::put('alerts:driver_locations:last_oldest_recorded_at', $oldestRecorded, now()->addHours(2));

        if ($staleCount === 0) {
            return Command::SUCCESS;
        }

        $cooldownKey = 'alerts:driver_locations:last_alert_at';
        if (Cache::has($cooldownKey)) {
            return Command::SUCCESS;
        }

        Cache::put($cooldownKey, now()->toISOString(), now()->addMinutes($cooldownMinutes));

        if (config('logging.channels.slack.url')) {
            $sampleIds = $staleUserIds
                ->merge($missingUserIds)
                ->unique()
                ->take(5)
                ->values()
                ->toArray();

            Log::channel('slack')->warning('Driver location updates stale', [
                'online_drivers' => $onlineUserIds->count(),
                'stale_count' => $staleCount,
                'missing_locations' => $missingUserIds->count(),
                'threshold_minutes' => $thresholdMinutes,
                'oldest_recorded_at' => $oldestRecorded,
                'sample_user_ids' => $sampleIds,
            ]);
        }

        return Command::SUCCESS;
    }
}

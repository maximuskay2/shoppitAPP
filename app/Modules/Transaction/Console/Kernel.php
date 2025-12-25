<?php

namespace App\Modules\Transaction\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        $schedule->command('app:subscription-reminders-command')
            ->daily()
            ->withoutOverlapping();
        $schedule->command('app:subscription-expired-command')
            ->daily()
            ->withoutOverlapping();
        $schedule->command('app:subscription-revert-reminder-command')
            ->daily()
            ->withoutOverlapping();
        $schedule->command('app:subscription-revert-command')
            ->daily()
            ->withoutOverlapping();
        $schedule->command('app:fail-pending-wallet-funding-transactions')
            ->everyFiveMinutes()
            ->withoutOverlapping();
        $schedule->command('app:fail-pending-orders')
            ->everyFiveMinutes()
            ->withoutOverlapping();
        }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

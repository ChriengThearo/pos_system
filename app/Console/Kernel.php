<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $enabled = filter_var((string) env('TELEGRAM_STOCK_ALERT_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
        $runSource = strtolower(trim((string) env('TELEGRAM_STOCK_ALERT_RUN_SOURCE', 'popup')));

        if ($enabled && $runSource === 'scheduler') {
            $script = base_path('python/telegram/stock_alert_monitor.py');
            if (is_file($script)) {
                $schedule
                    ->exec('python "'.$script.'" --once')
                    ->everyMinute()
                    ->withoutOverlapping();
            }
        }
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

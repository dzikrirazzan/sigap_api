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
        // Auto-generate shift setiap hari jam 2 pagi
        // Generate 7 hari ke depan berdasarkan weekly pattern
        $schedule->command('shifts:auto-generate --days=7')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/auto-shift-generation.log'));

        // Cleanup log file setiap minggu (agar tidak terlalu besar)
        $schedule->call(function () {
            $logFile = storage_path('logs/auto-shift-generation.log');
            if (file_exists($logFile) && filesize($logFile) > 5 * 1024 * 1024) { // 5MB
                file_put_contents($logFile, ''); // Clear log
            }
        })->weekly();
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

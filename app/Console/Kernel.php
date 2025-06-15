<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;


class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Clean expired security blacklist entries every hour
        $schedule->command('security:clean-blacklist --force')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        // Clean old violation records every day at 2 AM
        $schedule->command('security:clean-blacklist --force --days=30')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

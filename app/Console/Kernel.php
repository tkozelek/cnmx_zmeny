<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Laravel\Pulse\Contracts\Storage;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Needs the server cron: * * * * * php artisan schedule:run
        $schedule->command('app:prune-personal-data')->dailyAt('03:30');
        $schedule->command('auth:clear-resets')->daily();
        $schedule->command('queue:prune-failed', ['--hours' => 24 * 30])->daily();

        // Laravel only clears expired session files by lottery, and Pulse trims the same way - on a
        // quiet site both can linger for weeks past what the privacy page promises.
        $schedule->call(fn () => app('session')->driver()->getHandler()->gc(config('session.lifetime') * 60))->hourly();
        $schedule->call(fn () => app(Storage::class)->trim())->daily();
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

<?php

namespace App\Console;

use App\Jobs\ArchiveExpiredBlockedAccounts;
use App\Jobs\UnarchiveExpiredBlockedAccounts;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
         // $schedule->command('inspire')->hourly();

         // Schedule archiving job daily at 1 AM
         $schedule->job(new ArchiveExpiredBlockedAccounts)->dailyAt('01:00');

         // Schedule unarchiving job daily at 2 AM
         $schedule->job(new UnarchiveExpiredBlockedAccounts)->dailyAt('02:00');
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

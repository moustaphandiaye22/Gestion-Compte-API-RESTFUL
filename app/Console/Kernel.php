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

         // Schedule unarchiving job first (reactivate expired blocked accounts)
         $schedule->job(new UnarchiveExpiredBlockedAccounts)->dailyAt('01:00');

         // Schedule archiving job after (archive newly expired accounts)
         $schedule->job(new ArchiveExpiredBlockedAccounts)->dailyAt('02:00');
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

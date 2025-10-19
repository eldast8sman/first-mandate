<?php

namespace App\Console;

use App\Http\Controllers\CronController;
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
        
        // Schedule reminder emails to run every 6 hours
        $schedule->call(function () {
            $cron = new CronController();
            $cron->sendReminderEmails();
        })->everySixHours()->name('send-reminder-emails');
        
        // Schedule notice reminder emails to run daily at 9:00 AM
        $schedule->call(function () {
            $cron = new CronController();
            $cron->sendNoticeReminderEmails();
        })->dailyAt('09:00')->name('send-notice-reminder-emails');
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

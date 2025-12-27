<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\RetryFailedAttestationsJob;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('queue:work --stop-when-empty --queue=emails --tries=3')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Relance automatique toutes les 2 heures
        $schedule->job(new RetryFailedAttestationsJob())
            ->everyTwoHours()
            ->between('8:00', '20:00'); // Seulement en journée

        // Ou utiliser la commande
        $schedule->command('attestations:retry-failed --hours=6 --limit=30')
            ->hourly();
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

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
        // ✅ Traiter les emails toutes les minutes (1 job à la fois)
        $schedule->command('queue:work --stop-when-empty --queue=emails --tries=3 --max-jobs=1 --timeout=120')
            ->everyMinute()
            ->withoutOverlapping(3)
            ->runInBackground();

        // Une seule stratégie de relance est planifiée afin d'éviter les doublons.
        $schedule->command('attestations:retry-failed --hours=6 --limit=30')
            ->hourly()
            ->withoutOverlapping(10);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ResetEmailLimits extends Command
{
    protected $signature = 'email:reset-limits';
    protected $description = 'Réinitialiser les limites d\'envoi d\'emails';

    public function handle()
    {
        // Supprimer tous les compteurs
        Cache::forget('global_email_delay_lock');

        // Supprimer tous les compteurs horaires
        $keys = [
            'email_count_hour_' . date('Y-m-d-H'),
            'email_count_hour_' . date('Y-m-d-H', strtotime('-1 hour')),
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
            $this->info("Supprimé: {$key}");
        }

        $this->info("✅ Toutes les limites ont été réinitialisées !");
        $this->info("Vous pouvez maintenant envoyer des emails normalement.");

        return 0;
    }
}

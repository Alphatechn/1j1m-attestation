<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class UnblockHostinger extends Command
{
    protected $signature = 'email:unblock';
    protected $description = 'Débloquer Hostinger après un rate limit';

    public function handle()
    {
        Cache::forget('hostinger_blocked_until');
        Cache::forget('last_email_sent_timestamp');

        $hourKey = 'email_count_hour_' . date('Y-m-d-H');
        Cache::forget($hourKey);

        $this->info("✅ Hostinger a été débloqué !");
        $this->info("📊 Compteurs réinitialisés.");
        $this->info("⚠️ ATTENTION: Attendez au moins 5 minutes avant de réessayer.");
        $this->info("💡 Recommendation: Ne pas envoyer plus de 2-3 emails d'affilée.");

        return 0;
    }
}

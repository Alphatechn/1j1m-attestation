<?php

namespace App\Jobs;

use App\Models\Attestation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryFailedAttestationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 300;

    public function handle()
    {
        Log::info('🔁 Début de la relance automatique des attestations échouées');

        $failedAttestations = Attestation::where('email_status', 'failed')
            ->where('created_at', '>=', now()->subDays(7)) // 7 derniers jours
            ->where('retry_count', '<', 3) // Maximum 3 tentatives
            ->orderBy('created_at', 'asc')
            ->get();

        Log::info("📊 {$failedAttestations->count()} attestations à relancer");

        $delay = 0;
        foreach ($failedAttestations as $attestation) {
            try {
                // Mettre à jour le compteur de tentatives
                $attestation->increment('retry_count');

                // Relancer avec un délai progressif
                SendAttestationEmail::dispatch($attestation)
                    ->delay(now()->addSeconds($delay));

                $delay += 35; // Espacer de 35 secondes

                Log::info("🔄 Relance programmée: {$attestation->attestation_number}");

            } catch (\Exception $e) {
                Log::error("❌ Erreur lors de la relance de {$attestation->id}: " . $e->getMessage());
            }
        }

        Log::info('✅ Fin de la relance automatique');
    }
}

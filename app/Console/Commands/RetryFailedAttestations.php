<?php

namespace App\Console\Commands;

use App\Models\Attestation;
use App\Jobs\SendAttestationEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RetryFailedAttestations extends Command
{
    protected $signature = 'attestations:retry-failed
                            {--hours=24 : Nombre d\'heures à regarder en arrière}
                            {--status=failed : Statut à rechercher (failed, pending)}
                            {--limit=50 : Nombre maximum à relancer}
                            {--dry-run : Afficher seulement ce qui serait relancé}';

    protected $description = 'Relancer les attestations dont l\'envoi a échoué';

    public function handle()
    {
        $hours = $this->option('hours');
        $status = $this->option('status');
        $limit = $this->option('limit');
        $dryRun = $this->option('dry-run');

        $query = Attestation::where('email_status', $status)
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        $count = $query->count();

        if ($count === 0) {
            $this->info("Aucune attestation avec statut '{$status}' trouvée dans les dernières {$hours} heures.");
            return 0;
        }

        $this->info("📊 {$count} attestations à relancer");

        if ($dryRun) {
            $this->table(
                ['ID', 'Numéro', 'Statut', 'Erreur', 'Créée le'],
                $query->get(['id', 'attestation_number', 'email_status', 'email_error', 'created_at'])->toArray()
            );
            return 0;
        }

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $relancees = 0;
        $query->chunk(20, function ($attestations) use ($progressBar, &$relancees) {
            foreach ($attestations as $attestation) {
                try {
                    // Réinitialiser le statut
                    $attestation->update([
                        'email_status' => 'pending',
                        'email_error' => null,
                        'sent_at' => null
                    ]);

                    // Relancer le job
                    SendAttestationEmail::dispatch($attestation);

                    $relancees++;
                    Log::info("🔄 Attestation relancée: {$attestation->attestation_number}");

                } catch (\Exception $e) {
                    Log::error("❌ Erreur relance attestation {$attestation->id}: " . $e->getMessage());
                }

                $progressBar->advance();

                // Petit délai pour éviter la surcharge
                usleep(100000); // 0.1 seconde
            }
        });

        $progressBar->finish();
        $this->newLine();
        $this->info("✅ {$relancees} attestations ont été relancées avec succès");

        return 0;
    }
}

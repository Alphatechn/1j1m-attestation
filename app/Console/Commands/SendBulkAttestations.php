<?php

namespace App\Console\Commands;

use App\Models\Participant;
use App\Models\Attestation;
use App\Jobs\SendAttestationEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * ========================================
 * COMMAND POUR ENVOI MASSIF SÉCURISÉ
 * ========================================
 *
 * Usage:
 * php artisan attestations:send-bulk --periode=1
 * php artisan attestations:send-bulk --periode=1 --limit=50
 * php artisan attestations:send-bulk --all
 */
class SendBulkAttestations extends Command
{
    protected $signature = 'attestations:send-bulk
                            {--periode=* : ID(s) de période(s)}
                            {--all : Envoyer pour tous les participants sans attestation}
                            {--limit=100 : Nombre max d\'attestations à créer}
                            {--dry-run : Simuler sans envoyer}';

    protected $description = 'Envoi massif d\'attestations avec rate limiting Hostinger';

    public function handle()
    {
        $this->info('🚀 Démarrage de l\'envoi massif d\'attestations');
        $this->info('⏰ ' . now()->format('Y-m-d H:i:s'));
        $this->newLine();

        // ✅ Vérifier blocage Hostinger
        $blockedUntil = Cache::get('hostinger_blocked_until', 0);
        if ($blockedUntil > time()) {
            $waitMinutes = ceil(($blockedUntil - time()) / 60);
            $this->error("🚫 Hostinger a bloqué les envois.");
            $this->error("Attendez {$waitMinutes} minutes avant de réessayer.");
            return Command::FAILURE;
        }

        // ✅ Récupérer les participants
        $query = Participant::whereDoesntHave('attestations');

        if ($this->option('periode')) {
            $periodes = (array) $this->option('periode');
            $query->whereIn('periode_id', $periodes);
        } elseif (!$this->option('all')) {
            $this->error('❌ Spécifiez --periode=ID ou --all');
            return Command::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $participants = $query->with('periode')
            ->whereNotNull('email')
            ->limit($limit)
            ->get();

        if ($participants->isEmpty()) {
            $this->warn('⚠️  Aucun participant trouvé');
            return Command::SUCCESS;
        }

        $this->info("📊 {$participants->count()} participant(s) trouvé(s)");
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('🔍 MODE DRY-RUN (simulation)');
            $this->table(
                ['ID', 'Nom', 'Email', 'Période'],
                $participants->map(fn($p) => [
                    $p->id,
                    $p->full_name,
                    $p->email,
                    $p->periode->full_libelle ?? 'N/A'
                ])
            );
            return Command::SUCCESS;
        }

        // ✅ Confirmation
        if (!$this->confirm("Envoyer {$participants->count()} attestation(s) ?", true)) {
            $this->info('❌ Annulé');
            return Command::SUCCESS;
        }

        // ✅ Planifier les envois avec délai de 30 secondes entre chaque
        $this->info('📤 Planification des envois...');
        $bar = $this->output->createProgressBar($participants->count());
        $bar->start();

        $delaySeconds = 0;
        $successCount = 0;
        $errorCount = 0;

        foreach ($participants as $index => $participant) {
            try {
                // ✅ Créer l'attestation
                $attestation = Attestation::create([
                    'participant_id' => $participant->id,
                    'periode_id' => $participant->periode_id,
                    'generated_by' => 1, // ID admin système
                    'issue_date' => now(),
                    'status' => 'pending',
                    'content_text' => $this->generateContentText($participant),
                ]);

                // ✅ Planifier l'envoi avec délai
                SendAttestationEmail::dispatch($attestation)
                    ->onQueue('emails')
                    ->delay(now()->addSeconds($delaySeconds));

                $successCount++;
                $delaySeconds += 30; // 30 secondes entre chaque

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Erreur pour {$participant->full_name}: " . $e->getMessage());
                $errorCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // ✅ Résumé
        $this->info('✅ Planification terminée !');
        $this->newLine();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Succès', $successCount],
                ['Erreurs', $errorCount],
                ['Durée totale estimée', gmdate('H:i:s', $delaySeconds)],
                ['Fin prévue', now()->addSeconds($delaySeconds)->format('Y-m-d H:i:s')],
            ]
        );

        $this->newLine();
        $this->info('💡 Les emails seront envoyés progressivement par la queue.');
        $this->info('💡 Surveillez avec: php artisan queue:work');

        return Command::SUCCESS;
    }

    /**
     * Générer le contenu texte
     */
    private function generateContentText(Participant $participant): string
    {
        $periode = $participant->periode;
        return "Je soussigné(e), certifie que {$participant->full_name} " .
               "a participé à la formation/session organisée durant la période " .
               "{$periode->full_libelle}. Cette attestation est délivrée pour servir " .
               "et valoir ce que de droit.";
    }
}

<?php

namespace App\Jobs;

use App\Models\Attestation;
use App\Services\AttestationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SendAttestationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $attestation;
    public $tries = 3;
    public $timeout = 120;
    public $maxExceptions = 2;

    // ✅ CONSTANTES
    const MIN_DELAY_SECONDS = 40; // Délai minimum entre emails (augmenté pour sécurité)
    const MAX_EMAILS_PER_HOUR = 20;
    const GLOBAL_LOCK_SECONDS = 60; // Lock global LONG pour garantir l'espacement complet

    /**
     * The number of seconds to wait before retrying the job.
     */
    public function backoff()
    {
        return [30, 60, 120];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(Attestation $attestation)
    {
        $this->attestation = $attestation;
        $this->onQueue('emails');

        // ⚠️ IMPORTANT: Ne PAS définir delay ici, on le gère dans handle()
    }

    /**
     * Execute the job.
     */
    public function handle(AttestationService $attestationService)
    {
        // ✅ 1. Vérifier si déjà envoyé
        if ($this->attestation->email_status === 'sent') {
            Log::info("📧 [JOB] Email déjà envoyé: {$this->attestation->attestation_number}");
            return;
        }

        // ✅ 2. Vérifier blocage Hostinger
        if (!$this->checkHostingerStatus()) {
            return;
        }

        // ✅ 3. Vérifier quota horaire
        if (!$this->checkHourlyQuota()) {
            return;
        }

        // ✅ 4. LOCK GLOBAL - PRENDRE LE LOCK EN PREMIER
        $globalLock = Cache::lock('email_global_send_lock', self::GLOBAL_LOCK_SECONDS);

        if (!$globalLock->get()) {
            // Un autre job est en cours, attendre 10 secondes
            Log::info("🔒 [JOB] Lock global actif, réessai dans 10s: {$this->attestation->attestation_number}");
            $this->release(10);
            return;
        }

        try {
            // ✅ 5. Vérifier ET ATTENDRE le délai minimum
            $this->enforceMinimumDelay();

            // ✅ 6. Lock spécifique à cette attestation
            $attestationLock = Cache::lock('email_lock_' . $this->attestation->id, 120);

            if (!$attestationLock->get()) {
                Log::warning("🔒 [JOB] Lock attestation actif");
                $this->release(10);
                return;
            }

            try {
                Log::info("🚀 [JOB] Début envoi: {$this->attestation->attestation_number}");

                // ✅ 7. ENVOYER L'EMAIL
                $attestationService->sendAttestationByEmail($this->attestation);

                // ✅ 8. Mettre à jour les compteurs APRÈS succès
                $this->updateCountersAfterSuccess();

                // ✅ 9. Mettre à jour le statut
                $this->attestation->update([
                    'email_status' => 'sent',
                    'status' => 'sent',
                    'sent_at' => now(),
                    'email_error' => null
                ]);

                Log::info("✅ [JOB] Email envoyé avec succès: {$this->attestation->attestation_number}");

                // ✅ 10. ATTENDRE 5 secondes de plus pour sécurité
                Log::info("⏳ [JOB] Pause de sécurité de 5s...");
                sleep(5);

            } finally {
                $attestationLock->release();
            }

        } catch (\Exception $e) {
            $this->handleSendingError($e);
        } finally {
            // ✅ LIBÉRER LE LOCK SEULEMENT MAINTENANT
            $globalLock->release();
            Log::info("🔓 [JOB] Lock global libéré");
        }
    }

    /**
     * ✅ Vérifier le statut Hostinger
     */
    private function checkHostingerStatus(): bool
    {
        $blockedUntil = Cache::get('hostinger_blocked_until', 0);

        if ($blockedUntil > time()) {
            $waitTime = $blockedUntil - time() + 5;
            Log::warning("🚫 [JOB] Hostinger bloqué pour {$waitTime}s");
            $this->release($waitTime);
            return false;
        }

        return true;
    }

    /**
     * ✅ Vérifier le quota horaire
     */
    private function checkHourlyQuota(): bool
    {
        $hourKey = 'email_count_hour_' . date('Y-m-d-H');
        $currentCount = Cache::get($hourKey, 0);

        if ($currentCount >= self::MAX_EMAILS_PER_HOUR) {
            Log::warning("🚫 [JOB] Quota horaire atteint: {$currentCount}/" . self::MAX_EMAILS_PER_HOUR);

            // Calculer le temps restant dans l'heure
            $minutesLeft = 60 - (int)date('i');
            $waitTime = $minutesLeft * 60;

            $this->release($waitTime);
            return false;
        }

        return true;
    }

    /**
     * ✅ Garantir le délai minimum entre envois (AVEC ATTENTE RÉELLE)
     */
    private function enforceMinimumDelay(): void
    {
        $lastSent = Cache::get('last_email_sent_timestamp', 0);

        if ($lastSent > 0) {
            $elapsed = time() - $lastSent;
            $requiredWait = self::MIN_DELAY_SECONDS - $elapsed;

            if ($requiredWait > 0) {
                Log::info("⏳ [JOB] Attente de {$requiredWait}s avant envoi (dernier: il y a {$elapsed}s)");

                // ✅ ATTENDRE RÉELLEMENT (sleep bloquant)
                sleep($requiredWait);

                Log::info("✅ [JOB] Attente terminée, envoi maintenant");
            }
        }
    }

    /**
     * ✅ Mettre à jour les compteurs après succès
     */
    private function updateCountersAfterSuccess(): void
    {
        $hourKey = 'email_count_hour_' . date('Y-m-d-H');

        // Incrémenter le compteur horaire
        Cache::increment($hourKey, 1);
        Cache::put($hourKey, Cache::get($hourKey, 0), 3600);

        // Enregistrer le timestamp
        Cache::put('last_email_sent_timestamp', time(), 300);

        $newCount = Cache::get($hourKey, 0);
        Log::info("📊 [JOB] Email #{$newCount}/" . self::MAX_EMAILS_PER_HOUR . " envoyé cette heure");
    }

    /**
     * ✅ Gérer les erreurs d'envoi
     */
    private function handleSendingError(\Exception $e): void
    {
        Log::error("❌ [JOB] Erreur: " . $e->getMessage());

        // Détecter rate limit Hostinger
        if (str_contains($e->getMessage(), '451') ||
            str_contains($e->getMessage(), 'rate limit') ||
            str_contains($e->getMessage(), 'too many') ||
            str_contains($e->getMessage(), '421')) {

            Log::critical("🚨 [JOB] RATE LIMIT HOSTINGER DÉTECTÉ");
            Cache::put('hostinger_blocked_until', time() + 3600, 3700);

            $this->attestation->update([
                'email_status' => 'failed',
                'email_error' => 'Rate limit Hostinger - Réessai dans 1h'
            ]);

            $this->release(3600);
        } else {
            // Réessayer avec backoff
            $attemptNumber = $this->attempts();
            $backoffTimes = $this->backoff();
            $waitTime = $backoffTimes[$attemptNumber - 1] ?? 60;

            Log::warning("⚠️ [JOB] Réessai dans {$waitTime}s (tentative {$attemptNumber}/{$this->tries})");

            $this->release($waitTime);
        }

        throw $e;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("💥 [JOB] Échec définitif: {$this->attestation->attestation_number}");
        Log::error("💥 [JOB] Erreur: " . $exception->getMessage());

        $this->attestation->update([
            'email_status' => 'failed',
            'status' => 'failed',
            'email_error' => substr($exception->getMessage(), 0, 200)
        ]);
    }

    /**
     * ✅ Calculer le délai avant l'exécution du job
     */
    public static function calculateDelay(int $position): int
    {
        // Premier job : délai de 0
        // Jobs suivants : 35 secondes * position
        return $position * self::MIN_DELAY_SECONDS;
    }
}

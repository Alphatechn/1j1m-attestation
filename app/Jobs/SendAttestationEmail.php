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

/**
 * ========================================
 * JOB POUR ENVOI ASYNCHRONE D'ATTESTATIONS
 * ========================================
 *
 * Usage:
 * SendAttestationEmail::dispatch($attestation)->delay(now()->addSeconds(30));
 */
class SendAttestationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $attestation;
    public $tries = 3; // ✅ 3 tentatives en cas d'échec
    public $timeout = 120; // ✅ Timeout de 2 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(Attestation $attestation)
    {
        $this->attestation = $attestation;
        $this->onQueue('emails'); // ✅ Queue dédiée
    }

    /**
     * Execute the job.
     */
    public function handle(AttestationService $attestationService)
    {
        try {
            // ✅ Vérifier blocage Hostinger
            $blockedUntil = Cache::get('hostinger_blocked_until', 0);
            if ($blockedUntil > time()) {
                $waitTime = $blockedUntil - time();
                Log::warning("🚫 Job en attente: Hostinger bloqué pour {$waitTime}s");
                $this->release($waitTime); // Réessayer après le déblocage
                return;
            }

            // ✅ Vérifier quota horaire
            $hourKey = 'email_count_hour_' . date('Y-m-d-H');
            $currentCount = Cache::get($hourKey, 0);

            if ($currentCount >= 20) {
                Log::warning("🚫 Job en attente: Quota horaire atteint");
                $this->release(300); // Réessayer dans 5 minutes
                return;
            }

            // ✅ Vérifier délai minimum
            $lastSent = Cache::get('last_email_sent_timestamp', 0);
            if ($lastSent > 0) {
                $elapsed = time() - $lastSent;
                if ($elapsed < 30) {
                    $waitTime = 30 - $elapsed;
                    Log::info("⏳ Job en attente: {$waitTime}s");
                    $this->release($waitTime);
                    return;
                }
            }

            // ✅ LOCK pour éviter concurrence
            $lock = Cache::lock('email_send_lock', 35);
            if (!$lock->get()) {
                Log::warning("🔒 Job en attente: Lock actif");
                $this->release(5); // Réessayer dans 5 secondes
                return;
            }

            try {
                Log::info("📧 [JOB] Envoi attestation: {$this->attestation->attestation_number}");

                // ✅ Envoyer l'email
                $attestationService->sendAttestationByEmail($this->attestation);

                // ✅ Incrémenter compteurs APRÈS succès
                Cache::increment($hourKey, 1, 3600);
                Cache::put('last_email_sent_timestamp', time(), 300);

                Log::info("✅ [JOB] Email envoyé avec succès");

            } finally {
                $lock->release();
            }

        } catch (\Exception $e) {
            Log::error("❌ [JOB] Erreur: " . $e->getMessage());

            // ✅ Détecter rate limit Hostinger
            if (str_contains($e->getMessage(), '451') ||
                str_contains($e->getMessage(), 'rate') ||
                str_contains($e->getMessage(), 'limit')) {

                Log::critical("🚨 [JOB] RATE LIMIT HOSTINGER");
                Cache::put('hostinger_blocked_until', time() + 3600, 3700);

                // Réessayer dans 1 heure
                $this->release(3600);
            } else {
                // Autres erreurs: réessayer avec backoff exponentiel
                $this->release(60 * $this->attempts());
            }

            throw $e; // Permet le retry automatique
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("❌ [JOB] Échec définitif après {$this->tries} tentatives");
        Log::error("Attestation: {$this->attestation->attestation_number}");
        Log::error("Erreur: " . $exception->getMessage());

        // ✅ Marquer l'attestation comme échouée
        $this->attestation->update([
            'email_status' => 'failed',
            'email_error' => substr($exception->getMessage(), 0, 200)
        ]);
    }
}

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

    /**
     * The number of seconds to wait before retrying the job.
     */
    public function backoff()
    {
        // Backoff exponentiel: 30s, 60s, 120s
        return [30, 60, 120];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(Attestation $attestation)
    {
        $this->attestation = $attestation;
        $this->onQueue('emails');

        // ✅ FORCER un délai minimum de 35 secondes entre chaque job
        $this->delay = now()->addSeconds(35);
    }

    /**
     * Execute the job.
     */
    public function handle(AttestationService $attestationService)
    {
        // ✅ Vérifier si déjà envoyé
        if ($this->attestation->email_status === 'sent') {
            Log::info("📧 [JOB] Email déjà envoyé: {$this->attestation->attestation_number}");
            return;
        }

        // ✅ Vérifier blocage Hostinger
        $blockedUntil = Cache::get('hostinger_blocked_until', 0);
        if ($blockedUntil > time()) {
            $waitTime = $blockedUntil - time() + 5;
            Log::warning("🚫 Job en attente: Hostinger bloqué pour {$waitTime}s");
            $this->release($waitTime);
            return;
        }

        // ✅ Vérifier quota horaire (20 emails/heure)
        $hourKey = 'email_count_hour_' . date('Y-m-d-H');
        $currentCount = Cache::get($hourKey, 0);

        if ($currentCount >= 20) {
            Log::warning("🚫 Quota horaire atteint: {$currentCount}/20");
            $this->release(300); // Réessayer dans 5 minutes
            return;
        }

        // ✅ Vérifier délai minimum entre envois (35 secondes)
        $lastSent = Cache::get('last_email_sent_timestamp', 0);
        $elapsed = time() - $lastSent;

        if ($lastSent > 0 && $elapsed < 35) {
            $waitTime = 35 - $elapsed;
            Log::info("⏳ Attente: {$waitTime}s avant envoi (dernier: {$elapsed}s)");
            $this->release($waitTime);
            return;
        }

        // ✅ LOCK pour éviter les envois concurrents
        $lockKey = 'email_send_lock_' . $this->attestation->id;
        $lock = Cache::lock($lockKey, 15);

        if (!$lock->get()) {
            Log::warning("🔒 Job en attente: Lock actif pour attestation");
            $this->release(5);
            return;
        }

        try {
            Log::info("🚀 [JOB] Début envoi: {$this->attestation->attestation_number}");

            // ✅ ENVOYER L'EMAIL
            $attestationService->sendAttestationByEmail($this->attestation);

            // ✅ Mettre à jour les compteurs APRÈS succès
            Cache::put('last_email_sent_timestamp', time(), 60);
            Cache::increment($hourKey, 1, now()->addHour());

            // ✅ Mettre à jour le statut
            $this->attestation->update([
                'email_status' => 'sent',
                'sent_at' => now(),
                'email_error' => null
            ]);

            Log::info("✅ [JOB] Email envoyé avec succès: {$this->attestation->attestation_number}");

        } catch (\Exception $e) {
            Log::error("❌ [JOB] Erreur: " . $e->getMessage());

            // Détecter rate limit
            if (str_contains($e->getMessage(), '451') ||
                str_contains($e->getMessage(), 'rate limit') ||
                str_contains($e->getMessage(), 'too many requests')) {

                Log::critical("🚨 RATE LIMIT HOSTINGER DÉTECTÉ");
                Cache::put('hostinger_blocked_until', time() + 3600, 3700);

                // Mettre à jour le statut
                $this->attestation->update([
                    'email_status' => 'failed',
                    'email_error' => 'Rate limit Hostinger'
                ]);

                $this->release(3600);
            } else {
                // Réessayer avec backoff
                $this->release($this->backoff()[$this->attempts() - 1] ?? 60);
            }

            throw $e;
        } finally {
            $lock->release();
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("💥 [JOB] Échec définitif: {$this->attestation->attestation_number}");

        $this->attestation->update([
            'email_status' => 'failed',
            'email_error' => substr($exception->getMessage(), 0, 200)
        ]);
    }
}

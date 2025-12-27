<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Attestation;
use App\Models\Periode;
use App\Jobs\SendAttestationEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * ========================================
 * CONTROLLER POUR ENVOI MASSIF VIA FRONTEND
 * ========================================
 */
class BulkAttestationController extends Controller
{
    /**
     * Page d'envoi massif
     */
    public function index()
    {
        // Récupérer les périodes actives
        $periodes = Periode::active()
            ->withCount(['participants' => function($query) {
                $query->whereDoesntHave('attestations')
                      ->whereNotNull('email');
            }])
            ->get();

        // Stats globales
        $stats = [
            'total_participants' => Participant::count(),
            'with_attestations' => Attestation::distinct('participant_id')->count(),
            'without_attestations' => Participant::whereDoesntHave('attestations')
                ->whereNotNull('email')
                ->count(),
            'pending_jobs' => DB::table('jobs')->where('queue', 'emails')->count(),
        ];

        return view('Attestations.bulk-send', compact('periodes', 'stats'));
    }

    /**
     * ✅ PRÉVISUALISER les participants avant envoi
     */
    public function preview(Request $request)
    {
        $request->validate([
            'periode_id' => 'nullable|exists:periodes,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $query = Participant::whereDoesntHave('attestations')
                ->whereNotNull('email')
                ->with('periode');

            // Filtrer par période si spécifié
            if ($request->filled('periode_id')) {
                $query->where('periode_id', $request->periode_id);
            }

            // Appliquer la limite
            $limit = $request->input('limit', 20);
            $participants = $query->limit($limit)->get();

            // Vérifier format email
            $validParticipants = [];
            $invalidParticipants = [];

            foreach ($participants as $participant) {
                if (filter_var($participant->email, FILTER_VALIDATE_EMAIL)) {
                    $validParticipants[] = [
                        'id' => $participant->id,
                        'full_name' => $participant->full_name,
                        'email' => $participant->email,
                        'periode' => $participant->periode->full_libelle ?? 'N/A',
                    ];
                } else {
                    $invalidParticipants[] = [
                        'id' => $participant->id,
                        'full_name' => $participant->full_name,
                        'email' => $participant->email,
                        'reason' => 'Format email invalide',
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'valid' => $validParticipants,
                    'invalid' => $invalidParticipants,
                    'total_valid' => count($validParticipants),
                    'total_invalid' => count($invalidParticipants),
                    'estimated_duration' => gmdate('H:i:s', count($validParticipants) * 30),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la prévisualisation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ENVOYER EN MASSE avec Queue
     */
    public function send(Request $request)
    {
        $request->validate([
            'periode_id' => 'nullable|exists:periodes,id',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'exists:participants,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            // ✅ Vérifier blocage Hostinger
            $blockedUntil = Cache::get('hostinger_blocked_until', 0);
            if ($blockedUntil > time()) {
                $waitMinutes = ceil(($blockedUntil - time()) / 60);
                return response()->json([
                    'success' => false,
                    'message' => "🚨 Hostinger a bloqué les envois. Attendez {$waitMinutes} minutes.",
                    'type' => 'hostinger_blocked'
                ], 503);
            }

            // ✅ Récupérer les participants
            $query = Participant::whereDoesntHave('attestations')
                ->whereNotNull('email');

            // Si IDs spécifiques fournis
            if ($request->filled('participant_ids')) {
                $query->whereIn('id', $request->participant_ids);
            }
            // Sinon filtrer par période
            elseif ($request->filled('periode_id')) {
                $query->where('periode_id', $request->periode_id);
            }

            // Appliquer la limite
            $limit = $request->input('limit', 20);
            $participants = $query->limit($limit)->get();

            if ($participants->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun participant trouvé sans attestation.'
                ], 404);
            }

            // ✅ Traiter les participants
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $delaySeconds = 0;

            DB::beginTransaction();

            try {
                foreach ($participants as $participant) {
                    try {
                        // Valider email
                        if (!filter_var($participant->email, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = [
                                'participant' => $participant->full_name,
                                'error' => 'Format email invalide: ' . $participant->email
                            ];
                            $errorCount++;
                            continue;
                        }

                        // ✅ Créer l'attestation
                        $attestation = Attestation::create([
                            'participant_id' => $participant->id,
                            'periode_id' => $participant->periode_id,
                            'generated_by' => auth()->id(),
                            'issue_date' => now(),
                            'status' => 'pending',
                            'content_text' => $this->generateContentText($participant),
                        ]);

                        // ✅ Planifier l'envoi avec délai progressif
                        SendAttestationEmail::dispatch($attestation)
                            ->onQueue('emails')
                            ->delay(now()->addSeconds($delaySeconds));

                        $successCount++;
                        $delaySeconds += 30; // 30 secondes entre chaque

                    } catch (\Exception $e) {
                        $errors[] = [
                            'participant' => $participant->full_name,
                            'error' => $e->getMessage()
                        ];
                        $errorCount++;
                        Log::error("Erreur création attestation: " . $e->getMessage());
                    }
                }

                DB::commit();

                Log::info("📊 Envoi massif planifié: {$successCount} succès, {$errorCount} erreurs");

                return response()->json([
                    'success' => true,
                    'message' => "Envoi massif planifié avec succès !",
                    'data' => [
                        'success_count' => $successCount,
                        'error_count' => $errorCount,
                        'errors' => $errors,
                        'estimated_duration' => gmdate('H:i:s', $delaySeconds),
                        'estimated_completion' => now()->addSeconds($delaySeconds)->format('Y-m-d H:i:s'),
                        'info' => [
                            'delay_between_emails' => '30 secondes',
                            'queue' => 'emails',
                            'status' => 'Les emails seront envoyés progressivement'
                        ]
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error("Erreur envoi massif: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi massif: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ STATUT de l'envoi massif
     */
    public function status()
    {
        try {
            $hourKey = 'email_count_hour_' . date('Y-m-d-H');
            $emailsSent = Cache::get($hourKey, 0);
            $lastSent = Cache::get('last_email_sent_timestamp', 0);
            $blockedUntil = Cache::get('hostinger_blocked_until', 0);

            $pendingJobs = DB::table('jobs')
                ->where('queue', 'emails')
                ->count();

            $failedJobs = DB::table('failed_jobs')
                ->where('queue', 'emails')
                ->count();

            // Attestations en attente d'envoi
            $pendingAttestations = Attestation::where('status', 'pending')
                ->orWhere('email_status', 'failed')
                ->count();

            // Dernières attestations envoyées
            $recentlySent = Attestation::where('status', 'sent')
                ->orderBy('sent_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($a) => [
                    'participant' => $a->participant->full_name,
                    'email' => $a->participant->email,
                    'sent_at' => $a->sent_at->diffForHumans(),
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'quota' => [
                        'sent_this_hour' => $emailsSent,
                        'max_per_hour' => 20,
                        'remaining' => max(0, 20 - $emailsSent),
                        'percentage' => ($emailsSent / 20) * 100,
                    ],
                    'timing' => [
                        'last_email_ago_seconds' => $lastSent > 0 ? time() - $lastSent : null,
                        'last_email_ago_human' => $lastSent > 0 ? gmdate('i:s', time() - $lastSent) . ' min' : 'Jamais',
                        'can_send_now' => $lastSent === 0 || (time() - $lastSent) >= 30,
                        'next_available_at' => $lastSent > 0 ? date('H:i:s', $lastSent + 30) : 'Maintenant',
                    ],
                    'hostinger' => [
                        'blocked' => $blockedUntil > time(),
                        'blocked_until' => $blockedUntil > time() ? date('H:i:s', $blockedUntil) : null,
                        'blocked_remaining' => $blockedUntil > time() ? gmdate('i:s', $blockedUntil - time()) : null,
                    ],
                    'queue' => [
                        'pending_jobs' => $pendingJobs,
                        'failed_jobs' => $failedJobs,
                        'pending_attestations' => $pendingAttestations,
                    ],
                    'recent' => $recentlySent,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ANNULER les jobs en attente
     */
    public function cancel()
    {
        try {
            $deletedCount = DB::table('jobs')
                ->where('queue', 'emails')
                ->delete();

            Log::info("🚫 {$deletedCount} job(s) annulé(s)");

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} envoi(s) annulé(s) avec succès.",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ RÉESSAYER les jobs échoués
     */
    public function retry()
    {
        try {
            // Récupérer les jobs échoués
            $failedJobs = DB::table('failed_jobs')
                ->where('queue', 'emails')
                ->get();

            if ($failedJobs->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun job échoué à réessayer.'
                ], 404);
            }

            $retriedCount = 0;
            foreach ($failedJobs as $failedJob) {
                try {
                    // Récupérer les données du job
                    $payload = json_decode($failedJob->payload, true);
                    $data = unserialize($payload['data']['command']);

                    // Re-dispatcher le job avec délai
                    SendAttestationEmail::dispatch($data->attestation)
                        ->onQueue('emails')
                        ->delay(now()->addSeconds($retriedCount * 30));

                    // Supprimer de la table failed_jobs
                    DB::table('failed_jobs')->where('id', $failedJob->id)->delete();

                    $retriedCount++;

                } catch (\Exception $e) {
                    Log::error("Erreur retry job {$failedJob->id}: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$retriedCount} job(s) réessayé(s) avec succès.",
                'retried_count' => $retriedCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retry: ' . $e->getMessage()
            ], 500);
        }
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

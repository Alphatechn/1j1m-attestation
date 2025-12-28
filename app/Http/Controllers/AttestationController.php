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
use Illuminate\Support\Facades\Validator;
use App\Services\AttestationService;

class AttestationController extends Controller
{
    protected $attestationService;

    public function __construct(AttestationService $attestationService)
    {
        $this->attestationService = $attestationService;

        $this->middleware('auth')->except([
            'verify',
            'searchByNamePage',
            'searchByName',
            'searchByNumberPage',
            'searchByNumber',
            'publicDownload',
            'publicPreview'
        ]);
    }

    // ==================== MÉTHODES PUBLIQUES ====================

    public function publicDownload($id)
    {
        try {
            Log::info("Téléchargement public - ID: {$id}");

            $attestation = Attestation::with(['participant', 'periode'])
                ->where('id', $id)
                ->first();

            if (!$attestation) {
                Log::warning("Attestation non trouvée: {$id}");
                abort(404, 'Attestation non trouvée');
            }

            Log::info("Attestation trouvée: {$attestation->attestation_number}");
            return $this->attestationService->downloadPDF($attestation);

        } catch (\Exception $e) {
            Log::error("Erreur téléchargement: " . $e->getMessage());
            abort(500, 'Erreur lors du téléchargement');
        }
    }

    public function publicPreview($id)
    {
        try {
            Log::info("Preview public - ID: {$id}");

            $attestation = Attestation::with(['participant', 'periode'])
                ->where('id', $id)
                ->first();

            if (!$attestation) {
                Log::warning("Attestation non trouvée: {$id}");
                abort(404, 'Attestation non trouvée');
            }

            Log::info("Attestation trouvée: {$attestation->attestation_number}");
            return $this->attestationService->displayPDF($attestation);

        } catch (\Exception $e) {
            Log::error("Erreur preview: " . $e->getMessage());
            abort(500, 'Erreur lors de la visualisation');
        }
    }

    public function verify($token)
    {
        $attestation = $this->attestationService->viewAttestation($token, 'token');

        if (!$attestation) {
            return view('Attestations.verify', [
                'valid' => false,
                'message' => 'Code QR invalide ou attestation introuvable.'
            ]);
        }

        return view('Attestations.verify', [
            'valid' => true,
            'attestation' => $attestation,
            'participant' => $attestation->participant,
            'periode' => $attestation->periode,
        ]);
    }

    public function searchByNamePage()
    {
        return view('Attestations.search-by-name');
    }

    public function searchByName(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:255',
        ], [
            'name.required' => 'Le nom complet est obligatoire.',
            'name.min' => 'Le nom complet doit contenir au moins 2 caractères.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $attestations = $this->attestationService->searchByParticipantName($request->name);

            return response()->json([
                'success' => true,
                'data' => $attestations,
                'count' => $attestations->count(),
                'message' => $attestations->count() > 0
                    ? "Attestation trouvée"
                    : "Aucune attestation trouvée pour \"{$request->name}\""
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche: ' . $e->getMessage()
            ], 500);
        }
    }

    public function searchByNumberPage()
    {
        return view('Attestations.search-by-number');
    }

    public function searchByNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attestation_number' => 'required|string|min:5|max:50',
        ], [
            'attestation_number.required' => 'Le numéro d\'attestation est obligatoire.',
            'attestation_number.min' => 'Le numéro d\'attestation doit contenir au moins 5 caractères.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $attestation = $this->attestationService->viewAttestation(
                $request->attestation_number,
                'number'
            );

            if (!$attestation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune attestation trouvée avec ce numéro.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $attestation->load(['participant', 'periode']),
                'message' => 'Attestation trouvée avec succès!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== MÉTHODES ADMIN ====================

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Attestation::with(['participant', 'periode', 'generatedBy']);

            if ($request->filled('periode_id')) {
                $query->where('periode_id', $request->periode_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('attestation_number', 'LIKE', "%{$search}%")
                      ->orWhereHas('participant', function($q) use ($search) {
                          $q->where('name', 'LIKE', "%{$search}%");
                      });
                });
            }

            $attestations = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $attestations
            ]);
        }

        $periodes = Periode::active()->get();
        return view('Attestations.index', compact('periodes'));
    }

    /**
     * ✅ Création individuelle OU en masse avec Queue
     */
    public function store(Request $request)
    {
        $request->validate([
            'participant_id' => 'required|exists:participants,id',
            'use_queue' => 'nullable|boolean',
        ]);

        try {
            $participant = Participant::findOrFail($request->participant_id);

            // Vérifier attestation existante
            $existing = Attestation::where('participant_id', $participant->id)
                ->where('periode_id', $participant->periode_id)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une attestation existe déjà pour ce participant.',
                    'existing_attestation' => $existing->attestation_number
                ], 422);
            }

            // ✅ OPTION 1: Utiliser la Queue (pour envoi massif)
            if ($request->input('use_queue', false)) {
                return $this->storeWithQueue($participant);
            }

            // ✅ OPTION 2: Envoi immédiat (pour envoi unique)
            return $this->storeImmediate($participant);

        } catch (\Exception $e) {
            Log::error("❌ Erreur création: " . $e->getMessage());

            $message = $e->getMessage();
            $statusCode = 500;

            if (str_contains($message, 'attendre') || str_contains($message, 'Attendez')) {
                $statusCode = 429;
            } elseif (str_contains($message, 'Hostinger a bloqué')) {
                $statusCode = 503;
            } elseif (str_contains($message, 'Format d\'email invalide')) {
                $statusCode = 422;
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'type' => $statusCode === 429 ? 'rate_limit' : 'error'
            ], $statusCode);
        }
    }

    /**
     * ✅ Création avec Queue
     */
    private function storeWithQueue(Participant $participant)
    {
        // Créer l'attestation
        $attestation = Attestation::create([
            'participant_id' => $participant->id,
            'periode_id' => $participant->periode_id,
            'generated_by' => auth()->id(),
            'issue_date' => now(),
            'status' => 'pending',
            'content_text' => $this->attestationService->generateContentText($participant),
        ]);

        // ✅ Planifier IMMÉDIATEMENT (le job gérera le délai en interne)
        SendAttestationEmail::dispatch($attestation)
            ->onQueue('emails');

        Log::info("📦 Attestation créée et planifiée: {$attestation->attestation_number}");

        return response()->json([
            'success' => true,
            'message' => 'Attestation créée et planifiée pour envoi.',
            'attestation' => $attestation->load('participant'),
            'queue_info' => [
                'status' => 'Le job gérera automatiquement les délais entre envois',
                'queue' => 'emails'
            ]
        ]);
    }

    /**
     * ✅ Création avec envoi immédiat
     */
    private function storeImmediate(Participant $participant)
    {
        Log::info("🔄 Création attestation avec envoi immédiat pour: {$participant->email}");

        $attestation = $this->attestationService->createAttestation(
            $participant,
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Attestation créée et envoyée avec succès.',
            'attestation' => $attestation->load('participant'),
            'info' => [
                'next_email_in' => '30+ secondes',
                'hourly_limit' => '20 emails/heure'
            ]
        ]);
    }

    /**
     * ✅ AMÉLIORATION: Envoi massif avec délais CALCULÉS
     */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'periode_id' => 'required|exists:periodes,id',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'exists:participants,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            // Vérifier blocage Hostinger
            $blockedUntil = Cache::get('hostinger_blocked_until', 0);
            if ($blockedUntil > time()) {
                $waitMinutes = ceil(($blockedUntil - time()) / 60);
                return response()->json([
                    'success' => false,
                    'message' => "🚨 Hostinger a bloqué les envois. Attendez {$waitMinutes} minutes.",
                    'type' => 'hostinger_blocked'
                ], 503);
            }

            // Récupérer les participants
            $query = Participant::whereDoesntHave('attestations')
                ->whereNotNull('email')
                ->where('periode_id', $request->periode_id);

            if ($request->filled('participant_ids')) {
                $query->whereIn('id', $request->participant_ids);
            }

            $limit = $request->input('limit', 20);
            $participants = $query->limit($limit)->get();

            if ($participants->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun participant trouvé sans attestation.'
                ], 404);
            }

            // ✅ Créer et planifier avec délais CALCULÉS
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $position = 0; // Position dans la queue

            DB::beginTransaction();

            try {
                foreach ($participants as $participant) {
                    try {
                        // Valider email
                        if (!filter_var($participant->email, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = [
                                'participant' => $participant->full_name,
                                'error' => 'Format email invalide'
                            ];
                            $errorCount++;
                            continue;
                        }

                        // Créer l'attestation
                        $attestation = Attestation::create([
                            'participant_id' => $participant->id,
                            'periode_id' => $participant->periode_id,
                            'generated_by' => auth()->id(),
                            'issue_date' => now(),
                            'status' => 'pending',
                            'content_text' => $this->generateContentText($participant),
                        ]);

                        // ✅ CALCULER le délai progressif (45 secondes entre chaque)
                        $delaySeconds = $position * 45;

                        // ✅ Planifier avec délai calculé
                        SendAttestationEmail::dispatch($attestation)
                            ->onQueue('emails')
                            ->delay(now()->addSeconds($delaySeconds));

                        Log::info("📦 Job #{$position} planifié pour dans {$delaySeconds}s: {$attestation->attestation_number}");

                        $successCount++;
                        $position++;

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

                $totalDurationSeconds = ($position - 1) * 45; // 45 secondes entre chaque
                $estimatedCompletion = now()->addSeconds($totalDurationSeconds);

                Log::info("📊 Envoi massif planifié: {$successCount} jobs avec délais progressifs");

                return response()->json([
                    'success' => true,
                    'message' => "Envoi massif planifié avec succès !",
                    'data' => [
                        'success_count' => $successCount,
                        'error_count' => $errorCount,
                        'errors' => $errors,
                        'estimated_duration' => gmdate('H:i:s', $totalDurationSeconds),
                        'estimated_completion' => $estimatedCompletion->format('Y-m-d H:i:s'),
                        'info' => [
                            'delay_between_emails' => '45 secondes',
                            'queue' => 'emails',
                            'method' => 'Délais calculés + Sleep interne + Lock global',
                            'jobs_count' => $successCount,
                            'status' => 'Les jobs sont planifiés avec des délais progressifs calculés'
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
     * ✅ Statut de la queue
     */
    public function queueStatus()
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
                        'can_send_now' => $lastSent === 0 || (time() - $lastSent) >= 40,
                    ],
                    'hostinger' => [
                        'blocked' => $blockedUntil > time(),
                        'blocked_until' => $blockedUntil > time() ? date('H:i:s', $blockedUntil) : null,
                    ],
                    'queue' => [
                        'pending_jobs' => $pendingJobs,
                        'failed_jobs' => $failedJobs,
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== MÉTHODES EXISTANTES ====================

    public function show($id)
    {
        try {
            $attestation = Attestation::with(['participant', 'periode', 'generatedBy'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $attestation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Attestation non trouvée.'
            ], 404);
        }
    }

    public function sendEmail($id)
    {
        try {
            $blockedUntil = Cache::get('hostinger_blocked_until', 0);
            if ($blockedUntil > time()) {
                $waitMinutes = ceil(($blockedUntil - time()) / 60);
                return response()->json([
                    'success' => false,
                    'message' => "Hostinger a bloqué les envois. Attendez {$waitMinutes} minutes."
                ], 503);
            }

            $attestation = Attestation::with('participant')->findOrFail($id);

            if (!$attestation->participant->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le participant n\'a pas d\'adresse email.'
                ], 422);
            }

            if (!filter_var($attestation->participant->email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format d\'email invalide: ' . $attestation->participant->email
                ], 422);
            }

            $this->attestationService->sendAttestationByEmail($attestation);

            return response()->json([
                'success' => true,
                'message' => 'Attestation envoyée avec succès à ' . $attestation->participant->email
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function download($id)
    {
        try {
            $attestation = Attestation::with(['participant', 'periode'])->findOrFail($id);
            return $this->attestationService->downloadPDF($attestation);

        } catch (\Exception $e) {
            abort(404, 'Attestation non trouvée');
        }
    }

    public function preview($id)
    {
        try {
            $attestation = Attestation::with(['participant', 'periode'])->findOrFail($id);
            return $this->attestationService->displayPDF($attestation);

        } catch (\Exception $e) {
            abort(404, 'Attestation non trouvée');
        }
    }

    public function destroy($id)
    {
        try {
            $attestation = Attestation::findOrFail($id);
            $attestation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attestation supprimée avec succès.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression.'
            ], 500);
        }
    }

    public function stats(Request $request)
    {
        $hourKey = 'email_count_hour_' . date('Y-m-d-H');
        $emailsSentThisHour = Cache::get($hourKey, 0);
        $lastEmailTimestamp = Cache::get('last_email_sent_timestamp', 0);
        $blockedUntil = Cache::get('hostinger_blocked_until', 0);

        $stats = [
            'total' => Attestation::count(),
            'sent' => Attestation::where('status', 'sent')->count(),
            'pending' => Attestation::where('status', 'pending')->count(),
            'failed' => Attestation::where('email_status', 'failed')->count(),
            'total_views' => Attestation::sum('view_count'),
            'this_month' => Attestation::whereMonth('created_at', now()->month)->count(),

            'quota' => [
                'emails_sent_this_hour' => $emailsSentThisHour,
                'max_per_hour' => 20,
                'remaining' => max(0, 20 - $emailsSentThisHour),
                'last_email_ago' => $lastEmailTimestamp > 0 ? (time() - $lastEmailTimestamp) : null,
                'can_send_now' => $lastEmailTimestamp === 0 || (time() - $lastEmailTimestamp) >= 40,
                'hostinger_blocked' => $blockedUntil > time(),
                'blocked_until' => $blockedUntil > time() ? date('H:i:s', $blockedUntil) : null,
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
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

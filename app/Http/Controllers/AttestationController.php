<?php

namespace App\Http\Controllers;

use App\Models\Attestation;
use App\Models\Participant;
use App\Models\Periode;
use App\Services\AttestationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class AttestationController extends Controller
{
    protected $attestationService;

    public function __construct(AttestationService $attestationService)
    {
        $this->attestationService = $attestationService;

        // Exclure toutes les méthodes publiques
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

    /**
     * Téléchargement PUBLIC
     */
    public function publicDownload($id)
    {
        try {
            \Log::info("Téléchargement public - ID: {$id}");

            $attestation = Attestation::with(['participant', 'periode'])
                                    ->where('id', $id)
                                    ->first();

            if (!$attestation) {
                \Log::warning("Attestation non trouvée pour téléchargement public: {$id}");
                abort(404, 'Attestation non trouvée');
            }

            \Log::info("Attestation publique trouvée: {$attestation->attestation_number}");
            return $this->attestationService->downloadPDF($attestation);

        } catch (\Exception $e) {
            \Log::error("Erreur téléchargement public: " . $e->getMessage());
            abort(500, 'Erreur lors du téléchargement');
        }
    }

    /**
     * Preview PUBLIC
     */
    public function publicPreview($id)
    {
        try {
            \Log::info("Preview public - ID: {$id}");

            $attestation = Attestation::with(['participant', 'periode'])
                                    ->where('id', $id)
                                    ->first();

            if (!$attestation) {
                \Log::warning("Attestation non trouvée pour preview public: {$id}");
                abort(404, 'Attestation non trouvée');
            }

            \Log::info("Attestation publique trouvée pour preview: {$attestation->attestation_number}");
            return $this->attestationService->displayPDF($attestation);

        } catch (\Exception $e) {
            \Log::error("Erreur preview public: " . $e->getMessage());
            abort(500, 'Erreur lors de la visualisation');
        }
    }

    /**
     * Page de vérification publique (via QR Code)
     */
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

    /**
     * RECHERCHE PAR NOM - Page
     */
    public function searchByNamePage()
    {
        return view('Attestations.search-by-name');
    }

    /**
     * RECHERCHE PAR NOM - Traitement AJAX
     */
    public function searchByName(Request $request)
    {
        // Validation
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
            $attestations = $this->attestationService->searchByParticipantName(
                $request->name
            );

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

    /**
     * RECHERCHE PAR NUMERO - Page
     */
    public function searchByNumberPage()
    {
        return view('Attestations.search-by-number');
    }

    /**
     * RECHERCHE PAR NUMERO - Traitement AJAX
     */
    public function searchByNumber(Request $request)
    {
        // Validation
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

    // ==================== MÉTHODES ADMIN (Protégées) ====================

    /**
     * Liste des attestations
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Attestation::with(['participant', 'periode', 'generatedBy']);

            // Filtres
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

            $attestations = $query->latest()->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $attestations
            ]);
        }

        $periodes = Periode::active()->get();
        return view('Attestations.index', compact('periodes'));
    }

    /**
     * Créer une attestation
     */
    public function store(Request $request)
    {
        $request->validate([
            'participant_id' => 'required|exists:participants,id',
        ]);

        try {
            DB::beginTransaction();

            $participant = Participant::findOrFail($request->participant_id);

            // Vérifier si une attestation existe déjà
            $existing = Attestation::where('participant_id', $participant->id)
                                   ->where('periode_id', $participant->periode_id)
                                   ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une attestation existe déjà pour ce participant.'
                ], 422);
            }

            $attestation = $this->attestationService->createAttestation(
                $participant,
                auth()->id()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attestation créée avec succès.',
                'data' => $attestation->load(['participant', 'periode'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une attestation
     */
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

    /**
     * Envoyer l'attestation par email
     */
    public function sendEmail($id)
    {
        try {
            $attestation = Attestation::with('participant')->findOrFail($id);

            if (!$attestation->participant->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le participant n\'a pas d\'adresse email.'
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

    /**
     * Télécharger le PDF (ADMIN)
     */
    public function download($id)
    {
        try {
            $attestation = Attestation::with(['participant', 'periode'])->findOrFail($id);
            return $this->attestationService->downloadPDF($attestation);

        } catch (\Exception $e) {
            abort(404, 'Attestation non trouvée');
        }
    }

    /**
     * Visualiser le PDF dans le navigateur (ADMIN)
     */
    public function preview($id)
    {
        try {
            $attestation = Attestation::with(['participant', 'periode'])->findOrFail($id);
            return $this->attestationService->displayPDF($attestation);

        } catch (\Exception $e) {
            abort(404, 'Attestation non trouvée');
        }
    }

    /**
     * Supprimer une attestation
     */
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

    /**
     * Statistiques
     */
    public function stats(Request $request)
    {
        $stats = [
            'total' => Attestation::count(),
            'sent' => Attestation::where('status', 'sent')->count(),
            'pending' => Attestation::where('status', 'pending')->count(),
            'total_views' => Attestation::sum('view_count'),
            'this_month' => Attestation::whereMonth('created_at', now()->month)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}

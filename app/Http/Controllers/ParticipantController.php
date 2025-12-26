<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Periode;
use App\Http\Requests\ParticipantRequest;
use App\Exports\ParticipantsExport;
use App\Imports\ParticipantsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ParticipantController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage participants')->except([
        'index',                    // public function index()
        'show',                     // public function show()
        'export',                   // public function export()
        'downloadTemplate',         // public function downloadTemplate()
        'listByPeriode',            // public function listByPeriode()
        'withoutAttestation'        // public function withoutAttestation()
    ]);
    }

    /**
     * Liste des participants
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Participant::with(['periode', 'attestations']);

            // Filtres
            if ($request->filled('periode_id')) {
                $query->where('periode_id', $request->periode_id);
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            if ($request->filled('has_attestation')) {
                if ($request->has_attestation == '1') {
                    $query->has('attestations');
                } else {
                    $query->doesntHave('attestations');
                }
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('matricule', 'LIKE', "%{$search}%")
                      ->orWhere('organisation', 'LIKE', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $participants = $query;

            return response()->json([
                'success' => true,
                'data' => $participants
            ]);
        }

        $periodes = Periode::active()->get();
        return view('Participants.index', compact('periodes'));
    }

    /**
     * Afficher un participant
     */
    public function show($id, Request $request)
    {
        try {
            $participant = Participant::with(['periode', 'attestations'])->findOrFail($id);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $participant
                ]);
            }

            return view('participants.show', compact('participant'));

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Participant non trouvé.'
                ], 404);
            }
            abort(404);
        }
    }

    /**
     * Créer un participant
     */
    public function store(ParticipantRequest $request)
    {
        try {
            DB::beginTransaction();

            $participant = Participant::create($request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Participant créé avec succès.',
                'data' => $participant->load('periode')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un participant
     */

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $participant = Participant::findOrFail($id);

            // Validation manuelle pour contourner le problème de Rule::unique
            $validator = Validator::make($request->all(), [
                'periode_id' => 'required|exists:periodes,id',
                'name' => 'required|string|max:255',
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                    Rule::unique('participants')->ignore($participant->id),
                ],
                'phone' => 'nullable|string|max:20',
                // 'matricule' => [
                //     'nullable',
                //     'string',
                //     'max:50',
                //     Rule::unique('participants')->ignore($participant->id),
                // ],
                'organisation' => 'nullable|string|max:255',
                'fonction' => 'nullable|string|max:255',
                'is_active' => 'boolean',
            ], [
                'email.unique' => 'Cet email est déjà utilisé.',
                'matricule.unique' => 'Ce matricule est déjà utilisé.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $participant->update($validator->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Participant mis à jour avec succès.',
                'data' => $participant->fresh(['periode'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un participant
     */
    public function destroy($id)
    {
        try {
            $participant = Participant::findOrFail($id);

            // Vérifier s'il y a des attestations
            if ($participant->attestations()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce participant car il possède des attestations.'
                ], 422);
            }

            $participant->delete();

            return response()->json([
                'success' => true,
                'message' => 'Participant supprimé avec succès.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/Désactiver un participant
     */
    public function toggleStatus($id)
    {
        try {
            $participant = Participant::findOrFail($id);
            $participant->is_active = !$participant->is_active;
            $participant->save();

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès.',
                'data' => $participant
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut.'
            ], 500);
        }
    }

    /**
     * Import en masse (CSV/Excel)
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // 10MB max
            'periode_id' => 'required|exists:periodes,id'
        ]);

        try {
            DB::beginTransaction();

            $import = new ParticipantsImport($request->periode_id);

            // Importer le fichier
            Excel::import($import, $request->file('file'));

            DB::commit();

            $response = [
                'success' => true,
                'message' => 'Import effectué avec succès.',
                'data' => [
                    'imported' => $import->getImportedCount(),
                    'failed' => $import->getFailedCount(),
                    'total' => $import->getImportedCount() + $import->getFailedCount()
                ]
            ];

            // Ajouter les erreurs détaillées si il y en a
            if ($import->getFailedCount() > 0) {
                $response['errors'] = $import->getErrors();
                $response['message'] = "Import partiellement réussi. {$import->getImportedCount()} participants importés, {$import->getFailedCount()} échecs.";
            }

            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();

            // Gestion spécifique des erreurs d'import
            if (str_contains($e->getMessage(), 'You are not allowed to')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur: Le fichier est corrompu ou le format n\'est pas supporté.'
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export des participants
     */
    public function export(Request $request)
    {
        try {
            $periodeId = $request->get('periode_id');
            $periode = null;

            if ($periodeId) {
                $periode = Periode::find($periodeId);
            }

            $filename = 'participants_' . ($periode ? $periode->libelle : 'all') . '_' . date('Y-m-d_His') . '.xlsx';

            return Excel::download(new ParticipantsExport($periodeId), $filename);

        } catch (\Exception $e) {
            // Fallback si l'export échoue
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste pour select/dropdown (par période) avec recherche
     */
    public function listByPeriode($periodeId, Request $request)
    {
        try {
            $query = Participant::select('id', 'name', 'email', 'organisation')
                ->where('periode_id', $periodeId)
                ->active();

            // Recherche par terme
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('organisation', 'LIKE', "%{$search}%");
                });
            }

            $participants = $query->orderBy('name')
                ->get()
                ->map(function($participant) {
                    return [
                        'id' => $participant->id,
                        'name' => $participant->full_name,
                        'email' => $participant->email,
                        'organisation' => $participant->organisation,
                        'has_attestation' => $participant->hasAttestation()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $participants
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des participants.'
            ], 500);
        }
    }

    /**
     * Participants sans attestation
     */
    public function withoutAttestation(Request $request)
    {
        $query = Participant::with('periode')
            ->doesntHave('attestations')
            ->active();

        if ($request->filled('periode_id')) {
            $query->where('periode_id', $request->periode_id);
        }

        $participants = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $participants
        ]);
    }

/**
 * Télécharger le template d'import
 */
public function downloadTemplate()
{
    try {
        \Log::info('Download template accessed'); // Test logging

        $templateData = [
            ['nom', 'email', 'telephone', 'matricule', 'organisation', 'fonction'],
            ['John Doe', 'john.doe@example.com', '+1234567890', 'MAT001', 'Entreprise ABC', 'Manager'],
            ['Jane Smith', 'jane.smith@example.com', '+0987654321', 'MAT002', 'Société XYZ', 'Développeur'],
        ];

        $filename = 'template_import_participants_' . date('Y-m-d') . '.xlsx';

        // Créer un export simple
        $export = new class($templateData) implements \Maatwebsite\Excel\Concerns\FromArray {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function array(): array
            {
                return $this->data;
            }
        };

        return Excel::download($export, $filename);

    } catch (\Exception $e) {
        \Log::error('Template download error: ' . $e->getMessage());

        // Fallback CSV
        $filename = 'template_import_participants_' . date('Y-m-d') . '.csv';
        $csv = fopen('php://output', 'w');

        foreach ($templateData as $row) {
            fputcsv($csv, $row, ';');
        }

        fclose($csv);

        return response()->streamDownload(function() use ($templateData) {
            $output = fopen('php://output', 'w');
            foreach ($templateData as $row) {
                fputcsv($output, $row, ';');
            }
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}
}

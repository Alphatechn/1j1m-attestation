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
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ParticipantController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['publicForm', 'publicSubmit']);
        $this->middleware('permission:manage participants')->except([
        'index',                    // public function index()
        'show',                     // public function show()
        'export',                   // public function export()
        'downloadTemplate',         // public function downloadTemplate()
        'listByPeriode',            // public function listByPeriode()
        'withoutAttestation',       // public function withoutAttestation()
        'publicForm',
        'publicSubmit'
    ]);
    }

    public function publicForm()
    {
        return view('Participants.public-form');
    }

    public function publicSubmit(Request $request)
    {
        $periode = Periode::active()
            ->orderByDesc('date_debut')
            ->orderByDesc('id')
            ->first();

        if (!$periode) {
            return back()->withInput()->withErrors([
                'periode' => 'Aucune période active n\'est disponible pour le moment. Veuillez réessayer plus tard.',
            ]);
        }

        $validated = $request->validate([
            'civility' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'city' => 'required|string|max:255',
            'whatsapp' => 'required|string|max:30',
            'training_group' => 'required|string|max:255',
            'classmates_contacts' => 'required|string|min:10',
            'course_delivery_explanation' => 'required|string|min:20',
            'technical_skills' => 'required|string|min:20',
            'public_speaking_description' => 'required|string|min:20',
            'ecommerce_steps' => 'required|string|min:20',
            'knowledge_use_plan' => 'required|string|min:20',
            'coaches' => 'required|string|min:10',
            'homework_screenshots' => 'required|array|min:1|max:6',
            'homework_screenshots.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'civility.required' => 'La civilité est obligatoire.',
            'name.required' => 'Les noms et prénoms complets sont obligatoires.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'Veuillez renseigner une adresse email valide.',
            'city.required' => 'La ville est obligatoire.',
            'whatsapp.required' => 'Le numéro WhatsApp est obligatoire.',
            'training_group.required' => 'Le groupe de formation est obligatoire.',
            'classmates_contacts.required' => 'Veuillez renseigner les noms et WhatsApp de 3 camarades.',
            'classmates_contacts.min' => 'Les informations sur vos camarades sont trop courtes.',
            'course_delivery_explanation.required' => 'Veuillez expliquer comment les cours sont dispensés à 1J1M.',
            'course_delivery_explanation.min' => 'Votre explication sur le déroulement des cours est trop courte.',
            'technical_skills.required' => 'Veuillez préciser les compétences techniques acquises.',
            'technical_skills.min' => 'Votre description des compétences techniques est trop courte.',
            'public_speaking_description.required' => 'Veuillez décrire votre méthode actuelle en art oratoire.',
            'public_speaking_description.min' => 'Votre description en art oratoire est trop courte.',
            'ecommerce_steps.required' => 'Veuillez décrire les étapes de mise en place d\'une activité e-commerce.',
            'ecommerce_steps.min' => 'Votre description des étapes e-commerce est trop courte.',
            'knowledge_use_plan.required' => 'Veuillez expliquer comment vous comptez mettre vos compétences à profit.',
            'knowledge_use_plan.min' => 'Votre plan d\'utilisation des compétences est trop court.',
            'coaches.required' => 'Veuillez renseigner vos 5 coachs et leurs matières.',
            'coaches.min' => 'Les informations sur vos coachs sont trop courtes.',
            'homework_screenshots.required' => 'Veuillez envoyer au moins une capture d\'écran d\'un devoir rendu.',
            'homework_screenshots.array' => 'Les captures doivent être envoyées sous forme de fichiers images.',
            'homework_screenshots.min' => 'Veuillez envoyer au moins une capture d\'écran.',
            'homework_screenshots.max' => 'Vous pouvez envoyer au maximum 6 captures d\'écran.',
            'homework_screenshots.*.image' => 'Chaque capture doit être une image.',
            'homework_screenshots.*.mimes' => 'Les captures doivent être au format JPG, PNG ou WEBP.',
            'homework_screenshots.*.max' => 'Chaque capture ne doit pas dépasser 4 Mo.',
        ]);

        $existingParticipant = Participant::with(['attestations' => fn ($query) => $query->latest()])
            ->where('email', $validated['email'])
            ->first();

        if ($existingParticipant) {
            $attestation = $existingParticipant->attestations->first();

            if ($attestation) {
                return back()->withInput()->with('existing_attestation', [
                    'message' => 'Une attestation existe déjà pour cette adresse email.',
                    'preview_url' => route('public.attestations.public.preview', $attestation->id),
                    'download_url' => route('public.attestations.public.download', $attestation->id),
                    'number' => $attestation->attestation_number,
                ]);
            }

            return back()->withInput()->withErrors([
                'email' => 'Cette adresse email existe déjà dans le système. Votre demande est peut-être déjà en attente de validation.',
            ]);
        }

        $paths = [];
        foreach ($request->file('homework_screenshots', []) as $file) {
            $paths[] = $file->store('homework_screenshots', 'public');
        }

        Participant::create([
            'periode_id' => $periode->id,
            'civility' => $validated['civility'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'city' => $validated['city'],
            'phone' => $validated['whatsapp'],
            'whatsapp' => $validated['whatsapp'],
            'training_group' => $validated['training_group'],
            'classmates_contacts' => $validated['classmates_contacts'],
            'course_delivery_explanation' => $validated['course_delivery_explanation'],
            'technical_skills' => $validated['technical_skills'],
            'public_speaking_description' => $validated['public_speaking_description'],
            'ecommerce_steps' => $validated['ecommerce_steps'],
            'knowledge_use_plan' => $validated['knowledge_use_plan'],
            'coaches' => $validated['coaches'],
            'homework_screenshot_paths' => $paths,
            'is_active' => false,
            'validation_status' => 'pending',
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Votre demande a bien été envoyée. Elle sera vérifiée avant la génération de votre attestation.');
    }

    /**
     * Liste des participants
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Participant::with(['periode', 'attestations', 'validatedBy']);

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

            if ($request->filled('validation_status')) {
                $query->where('validation_status', $request->validation_status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('city', 'LIKE', "%{$search}%")
                      ->orWhere('training_group', 'LIKE', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $participants = $query->get();

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
            $participant = Participant::with(['periode', 'attestations', 'validatedBy'])->findOrFail($id);

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

            if ($participant->validation_status === 'validated' && !$participant->validated_at) {
                $participant->update([
                    'validated_at' => now(),
                    'validated_by' => auth()->id(),
                    'is_active' => true,
                ]);
            }

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
                'whatsapp' => 'nullable|string|max:30',
                'city' => 'nullable|string|max:255',
                'civility' => 'nullable|string|max:20',
                'training_group' => 'nullable|string|max:255',
                'classmates_contacts' => 'nullable|string',
                'course_delivery_explanation' => 'nullable|string',
                'technical_skills' => 'nullable|string',
                'public_speaking_description' => 'nullable|string',
                'ecommerce_steps' => 'nullable|string',
                'knowledge_use_plan' => 'nullable|string',
                'coaches' => 'nullable|string',
                'validation_status' => 'nullable|in:pending,validated,rejected',
                'is_active' => 'boolean',
            ], [
                'email.unique' => 'Cet email est déjà utilisé.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            if (($data['validation_status'] ?? $participant->validation_status) === 'validated') {
                $data['validated_at'] = $participant->validated_at ?: now();
                $data['validated_by'] = $participant->validated_by ?: auth()->id();
                $data['is_active'] = $data['is_active'] ?? true;
            }

            $participant->update($data);

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

    public function validateParticipant($id)
    {
        $participant = Participant::findOrFail($id);

        $participant->update([
            'validation_status' => 'validated',
            'is_active' => true,
            'validated_at' => now(),
            'validated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Participant validé avec succès.',
            'data' => $participant->fresh(['periode', 'attestations', 'validatedBy'])
        ]);
    }

    public function bulkValidate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:participants,id',
        ], [
            'participant_ids.required' => 'Veuillez sélectionner au moins un participant.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $count = Participant::whereIn('id', $request->participant_ids)
            ->where('validation_status', 'pending')
            ->update([
                'validation_status' => 'validated',
                'is_active' => true,
                'validated_at' => now(),
                'validated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "{$count} participant(s) validé(s) avec succès.",
            'count' => $count,
        ]);
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
        $query = Participant::select('id', 'name', 'email', 'training_group')
            ->where('periode_id', $periodeId)
            ->active()
            ->validated();

            // Recherche par terme
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('training_group', 'LIKE', "%{$search}%");
                });
            }

            $participants = $query->orderBy('name')
                ->get()
                ->map(function($participant) {
                    return [
                        'id' => $participant->id,
                        'name' => $participant->full_name,
                        'email' => $participant->email,
                        'training_group' => $participant->training_group,
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
            ->active()
            ->validated();

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
        Log::info('Download template accessed'); // Test logging

        $templateData = [
            ['nom', 'email', 'telephone', 'ville', 'whatsapp', 'groupe_de_formation'],
            ['John Doe', 'john.doe@example.com', '+237690000000', 'Yaoundé', '+237690000000', 'Groupe A'],
            ['Jane Smith', 'jane.smith@example.com', '+237691111111', 'Douala', '+237691111111', 'Groupe B'],
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
        Log::error('Template download error: ' . $e->getMessage());

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

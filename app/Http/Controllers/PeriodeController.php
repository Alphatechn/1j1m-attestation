<?php

namespace App\Http\Controllers;

use App\Models\Periode;
use App\Http\Requests\PeriodeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeriodeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage periodes')->except(['index', 'show']);
    }

    /**
     * Liste des périodes
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Periode::withCount(['participants', 'attestations']);

            // Filtres
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('libelle', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $periodes = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $periodes
            ]);
        }

        return view('Periodes.index');
    }

    /**
     * Afficher une période
     */
    public function show($id, Request $request)
    {
        try {
            $periode = Periode::withCount(['participants', 'attestations'])->findOrFail($id);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $periode
                ]);
            }

            return view('periodes.show', compact('periode'));

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Période non trouvée.'
                ], 404);
            }
            abort(404);
        }
    }

    /**
     * Créer une période
     */
    public function store(PeriodeRequest $request)
    {
        try {
            DB::beginTransaction();

            $periode = Periode::create($request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Période créée avec succès.',
                'data' => $periode
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
     * Mettre à jour une période
     */
    public function update(PeriodeRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $periode = Periode::findOrFail($id);
            $periode->update($request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Période mise à jour avec succès.',
                'data' => $periode->fresh()
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
     * Supprimer une période
     */
    public function destroy($id)
    {
        try {
            $periode = Periode::findOrFail($id);

            // Vérifier s'il y a des participants ou attestations
            if ($periode->participants()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer cette période car elle contient des participants.'
                ], 422);
            }

            $periode->delete();

            return response()->json([
                'success' => true,
                'message' => 'Période supprimée avec succès.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/Désactiver une période
     */
    public function toggleStatus($id)
    {
        try {
            $periode = Periode::findOrFail($id);
            $periode->is_active = !$periode->is_active;
            $periode->save();

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès.',
                'data' => $periode
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut.'
            ], 500);
        }
    }

    /**
     * Liste pour select/dropdown
     */
    public function list(Request $request)
    {
        $query = Periode::select('id', 'libelle');

        if ($request->filled('active_only')) {
            $query->active();
        }

        $periodes = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $periodes
        ]);
    }

    /**
     * Statistiques d'une période
     */
    public function statistics($id)
    {
        try {
            $periode = Periode::findOrFail($id);

            $stats = [
                'total_participants' => $periode->participants()->count(),
                'total_attestations' => $periode->attestations()->count(),
                'attestations_sent' => $periode->attestations()->where('status', 'sent')->count(),
                'attestations_pending' => $periode->attestations()->where('status', 'pending')->count(),
                'participants_with_attestation' => $periode->participants()
                    ->has('attestations')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques.'
            ], 500);
        }
    }
}

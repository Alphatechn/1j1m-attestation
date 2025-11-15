<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Periode;
use App\Models\Participant;
use App\Models\Attestation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{

    public function accueil()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        return view('accueil');
    }
    /**
     * Afficher le dashboard
     */
    public function index()
    {
        // Debug: vérifier l'authentification
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Statistiques générales
        $stats = [
            'total_users' => User::active()->count(),
            'total_periodes' => Periode::count(),
            'active_periodes' => Periode::active()->count(),
            'total_participants' => Participant::count(),
            'total_attestations' => Attestation::count(),
            'attestations_sent' => Attestation::where('status', 'sent')->count(),
            'attestations_pending' => Attestation::where('status', 'pending')->count(),
        ];

        // Périodes récentes
        $recent_periodes = Periode::latest()->take(5)->get();

        // Attestations récentes
        $recent_attestations = Attestation::with(['participant', 'periode'])
            ->latest()
            ->take(10)
            ->get();

        // Participants sans attestation
        $participants_without_attestation = Participant::doesntHave('attestations')
            ->active()
            ->count();

        return view('Dashboard.index', compact(
            'stats',
            'recent_periodes',
            'recent_attestations',
            'participants_without_attestation',
            'user'
        ));
    }

    /**
     * Récupérer les statistiques (AJAX)
     */
    public function stats(Request $request)
    {
        // Statistiques générales
        $general = [
            'users' => [
                'total' => User::count(),
                'active' => User::active()->count(),
                'inactive' => User::inactive()->count(),
            ],
            'periodes' => [
                'total' => Periode::count(),
                'active' => Periode::active()->count(),
            ],
            'participants' => [
                'total' => Participant::count(),
                'active' => Participant::active()->count(),
                'with_attestation' => Participant::has('attestations')->count(),
                'without_attestation' => Participant::doesntHave('attestations')->count(),
            ],
            'attestations' => [
                'total' => Attestation::count(),
                'sent' => Attestation::where('status', 'sent')->count(),
                'pending' => Attestation::where('status', 'pending')->count(),
                'total_views' => Attestation::sum('view_count'),
            ],
        ];

        // Statistiques mensuelles (6 derniers mois)
        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthlyStats[] = [
                'month' => $date->format('M Y'),
                'participants' => Participant::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'attestations' => Attestation::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ];
        }

        // Top 5 périodes par nombre de participants
        $topPeriodes = Periode::withCount('participants')
            ->orderBy('participants_count', 'desc')
            ->take(5)
            ->get()
            ->map(function($periode) {
                return [
                    'libelle' => $periode->libelle,
                    'count' => $periode->participants_count,
                ];
            });

        // Statistiques par statut d'attestation
        $attestationsByStatus = DB::table('attestations')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // Activité récente (7 derniers jours)
        $recentActivity = [
            'new_participants' => Participant::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            'new_attestations' => Attestation::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            'sent_attestations' => Attestation::where('sent_at', '>=', Carbon::now()->subDays(7))->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'general' => $general,
                'monthly' => $monthlyStats,
                'top_periodes' => $topPeriodes,
                'by_status' => $attestationsByStatus,
                'recent_activity' => $recentActivity,
            ]
        ]);
    }
}

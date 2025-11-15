<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * Vérifie si l'utilisateur est actif et non supprimé
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ne pas vérifier sur la page de login pour éviter les boucles
        if ($request->routeIs('login') || $request->routeIs('login.post')) {
            return $next($request);
        }

        if (Auth::check()) {
            $user = Auth::user();

            // Vérifier si l'utilisateur est marqué comme supprimé
            if ($user->is_delete) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce compte n\'existe plus.',
                        'redirect' => route('login')
                    ], 403);
                }

                return redirect()->route('login')
                    ->with('error', 'Ce compte n\'existe plus.');
            }

            // Vérifier si l'utilisateur est actif
            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Votre compte est désactivé. Contactez l\'administrateur.',
                        'redirect' => route('login')
                    ], 403);
                }

                return redirect()->route('login')
                    ->with('error', 'Votre compte est désactivé. Contactez l\'administrateur.');
            }
        }

        return $next($request);
    }
}

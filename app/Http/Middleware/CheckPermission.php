<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!auth()->check()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié.'
                ], 401);
            }
            return redirect()->route('login');
        }

        if (!auth()->user()->can($permission)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas la permission d\'effectuer cette action.'
                ], 403);
            }

            abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }
}

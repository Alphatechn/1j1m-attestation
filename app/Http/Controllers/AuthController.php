<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Afficher le formulaire de connexion
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard.manage');
        }

        return view('Auth.login');
    }

    /**
     * Traiter la connexion
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // Vérifier le rate limiting
        $key = Str::lower($request->input('login')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'message' => "Trop de tentatives. Réessayez dans {$seconds} secondes."
            ], 429);
        }

        // Tentative de connexion
        $credentials = [
            'login' => $request->login,
            'password' => $request->password,
        ];

        // Vérifier si l'utilisateur existe et est actif
        $user = \App\Models\User::where('login', $request->login)->first();

        if (!$user) {
            RateLimiter::hit($key, 60);

            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects.'
            ], 422);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est désactivé. Contactez l\'administrateur.'
            ], 403);
        }

        if ($user->is_delete) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte n\'existe plus.'
            ], 403);
        }

        // Tentative d'authentification
        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            RateLimiter::clear($key);

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie.',
                'redirect' => route('home')
            ]);
        }

        // Échec de connexion
        RateLimiter::hit($key, 60);

        return response()->json([
            'success' => false,
            'message' => 'Identifiants incorrects.'
        ], 422);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie.',
                'redirect' => route('login')
            ]);
        }

        return redirect()->route('login')->with('success', 'Déconnexion réussie.');
    }
}

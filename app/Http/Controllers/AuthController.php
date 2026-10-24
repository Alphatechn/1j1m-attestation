<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard.manage');
        }

        return view('Auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::lower(trim($request->string('login')->toString())).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return $this->failure($request, 'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($key).' secondes.', 429);
        }

        $user = User::where('login', $request->input('login'))->first();

        if (!$user || !$user->is_active || $user->is_delete) {
            RateLimiter::hit($key, 60);

            return $this->failure($request, $user && !$user->is_active
                ? 'Votre compte est désactivé. Contactez l’administrateur.'
                : 'Identifiants incorrects.', $user ? 403 : 422);
        }

        if (!Auth::attempt($request->only('login', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            return $this->failure($request, 'Identifiants incorrects.');
        }

        $request->session()->regenerate();
        RateLimiter::clear($key);
        $redirect = $this->intendedUrl($request) ?? route('dashboard.manage');

        if (!$request->expectsJson()) {
            return redirect()->to($redirect)->with('success', 'Connexion réussie.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie.',
            'redirect' => $redirect,
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect' => route('login')]);
        }

        return redirect()->route('login')->with('success', 'Déconnexion réussie.');
    }

    private function failure(Request $request, string $message, int $status = 422)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }

        return back()->withInput($request->only('login', 'remember'))->with('error', $message);
    }

    private function intendedUrl(Request $request): ?string
    {
        $intended = $request->session()->get('url.intended');
        $host = $intended ? parse_url($intended, PHP_URL_HOST) : null;

        return !$host || $host === $request->getHost() ? $intended : null;
    }
}

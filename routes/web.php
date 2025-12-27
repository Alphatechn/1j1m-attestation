<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PeriodeController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\AttestationController;
use App\Http\Controllers\BulkAttestationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
/*
|--------------------------------------------------------------------------
| Routes Publiques
|--------------------------------------------------------------------------
*/
Route::name('public.')->group(function () {

    // Page d'accueil publique
    Route::get('/', function () {
        return view('Attestations.public-home');
    })->name('home');

    // Vérification par QR Code
    Route::get('/verify/{token}', [AttestationController::class, 'verify'])
         ->name('attestations.verify');

    // Recherche par nom
    Route::get('/search-by-name', [AttestationController::class, 'searchByNamePage'])
         ->name('search-by-name');
    Route::post('/search-by-name', [AttestationController::class, 'searchByName'])
         ->name('search-by-name.post');

    // Recherche par numéro
    Route::get('/search-by-number', [AttestationController::class, 'searchByNumberPage'])
         ->name('search-by-number');
    Route::post('/search-by-number', [AttestationController::class, 'searchByNumber'])
         ->name('search-by-number.post');

    // Routes publiques de téléchargement
    Route::get('/public-download/{id}', [AttestationController::class, 'publicDownload'])
         ->name('attestations.public.download');
    Route::get('/public-preview/{id}', [AttestationController::class, 'publicPreview'])
         ->name('attestations.public.preview');
});
/*
|--------------------------------------------------------------------------
| Routes d'Authentification
|--------------------------------------------------------------------------
*/


Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Routes Protégées (Authentification requise)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/home', [DashboardController::class, 'accueil'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.manage');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    /*
    |--------------------------------------------------------------------------
    | Gestion des Utilisateurs
    |--------------------------------------------------------------------------
    */
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/{id}', [UserController::class, 'show'])->name('show');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');

        // Profil utilisateur
        Route::get('/profile/me', [UserController::class, 'profile'])->name('profile');
        Route::put('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/password', [UserController::class, 'updatePassword'])->name('password.update');
    });

    /*
    |--------------------------------------------------------------------------
    | Gestion des Périodes
    |--------------------------------------------------------------------------
    */
    Route::prefix('periodes')->name('periodes.')->group(function () {
        Route::get('/', [PeriodeController::class, 'index'])->name('index');
        Route::get('/list', [PeriodeController::class, 'list'])->name('list');
        Route::get('/{id}', [PeriodeController::class, 'show'])->name('show');
        Route::post('/', [PeriodeController::class, 'store'])->name('store');
        Route::put('/{id}', [PeriodeController::class, 'update'])->name('update');
        Route::delete('/{id}', [PeriodeController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [PeriodeController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{id}/statistics', [PeriodeController::class, 'statistics'])->name('statistics');
    });

    /*
    |--------------------------------------------------------------------------
    | Gestion des Participants
    |--------------------------------------------------------------------------
    */
    Route::prefix('participants')->name('participants.')->group(function () {
        Route::get('/export', [ParticipantController::class, 'export'])->name('export');
        Route::get('/download-template', [ParticipantController::class, 'downloadTemplate'])->name('download-template');

        Route::get('/', [ParticipantController::class, 'index'])->name('index');
        Route::get('/{id}', [ParticipantController::class, 'show'])->name('show');
        Route::post('/', [ParticipantController::class, 'store'])->name('store');
        Route::put('/{id}', [ParticipantController::class, 'update'])->name('update');
        Route::delete('/{id}', [ParticipantController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [ParticipantController::class, 'toggleStatus'])->name('toggle-status');

        // Fonctionnalités supplémentaires
        Route::get('/periode/{periodeId}/list', [ParticipantController::class, 'listByPeriode'])->name('by-periode');
        Route::get('/without-attestation/list', [ParticipantController::class, 'withoutAttestation'])->name('without-attestation');

        // Import/Export
        Route::post('/import', [ParticipantController::class, 'import'])->name('import');

    });

    /*
    |--------------------------------------------------------------------------
    | Gestion des Attestations
    |--------------------------------------------------------------------------
    */
    Route::prefix('attestations')->name('attestations.')->group(function () {
        Route::get('/', [AttestationController::class, 'index'])->name('index');
        Route::get('/{id}', [AttestationController::class, 'show'])->name('show');
        Route::post('/', [AttestationController::class, 'store'])->name('store');
        Route::delete('/{id}', [AttestationController::class, 'destroy'])->name('destroy');

        // Génération et envoi
        Route::post('/{id}/send-email', [AttestationController::class, 'sendEmail'])->name('send-email');
        Route::get('/{id}/download', [AttestationController::class, 'download'])->name('download');
        Route::get('/{id}/preview', [AttestationController::class, 'preview'])->name('preview');

        // Statistiques
        Route::get('/stats/global', [AttestationController::class, 'stats'])->name('stats');

        Route::post('/bulk', [AttestationController::class, 'bulkStore']);
        Route::get('/queue/status', [AttestationController::class, 'queueStatus']);
    });
});

// /*
// |--------------------------------------------------------------------------
// | Route par défaut (redirection)
// |--------------------------------------------------------------------------
// */
Route::get('/welcome', function () {
    return view('welcome'); // Page d'accueil simple
})->name('welcome');
Route::get('/email-status', function() {
    $hourKey = 'email_count_hour_' . date('Y-m-d-H');
    $count = Cache::get($hourKey, 0);
    $delayLock = Cache::get('global_email_delay_lock', 0);

    $lastEmailAgo = $delayLock ? (time() - $delayLock) : 'Jamais';

    return response()->json([
        'status' => 'ok',
        'limites' => [
            'emails_cette_heure' => $count . '/30',
            'pourcentage' => round(($count / 30) * 100, 1) . '%',
            'dernier_email_il_y_a' => $lastEmailAgo . ' secondes',
            'prochain_email_possible_dans' => $delayLock ? max(0, 20 - $lastEmailAgo) : 0 . ' secondes',
            'reinitialisation_compteur' => (60 - date('i')) . ' minutes'
        ],
        'recommandation' => $count >= 25 ? '🚨 PRESQUE LIMITE - Attendez avant plus d\'envois' : '✅ OK pour envoyer'
    ]);
});


Route::middleware('auth')->group(function () {

    // Page d'envoi massif
    Route::get('/attestations/bulk', [BulkAttestationController::class, 'index'])
        ->name('attestations.bulk.index');

    // Prévisualiser avant envoi
    Route::post('/attestations/bulk/preview', [BulkAttestationController::class, 'preview'])
        ->name('attestations.bulk.preview');

    // Envoyer en masse
    Route::post('/attestations/bulk/send', [BulkAttestationController::class, 'send'])
        ->name('attestations.bulk.send');

    // Statut de l'envoi massif
    Route::get('/attestations/bulk/status', [BulkAttestationController::class, 'status'])
        ->name('attestations.bulk.status');

    // Annuler les envois en attente
    Route::post('/attestations/bulk/cancel', [BulkAttestationController::class, 'cancel'])
        ->name('attestations.bulk.cancel');

    // Réessayer les échecs
    Route::post('/attestations/bulk/retry', [BulkAttestationController::class, 'retry'])
        ->name('attestations.bulk.retry');
});

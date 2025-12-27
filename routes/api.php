<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/attestations/monitoring', function() {
    $hourKey = 'email_count_hour_' . date('Y-m-d-H');
    $emailsSent = Cache::get($hourKey, 0);
    $lastSent = Cache::get('last_email_sent_timestamp', 0);
    $blockedUntil = Cache::get('hostinger_blocked_until', 0);

    return response()->json([
        'quota' => [
            'sent_this_hour' => $emailsSent,
            'max_per_hour' => 20,
            'remaining' => max(0, 20 - $emailsSent),
            'percentage' => ($emailsSent / 20) * 100,
        ],
        'timing' => [
            'last_email_ago_seconds' => $lastSent > 0 ? time() - $lastSent : null,
            'can_send_now' => $lastSent === 0 || (time() - $lastSent) >= 30,
            'next_available_at' => $lastSent > 0
                ? date('Y-m-d H:i:s', $lastSent + 30)
                : 'Maintenant',
        ],
        'hostinger' => [
            'blocked' => $blockedUntil > time(),
            'blocked_until' => $blockedUntil > time()
                ? date('Y-m-d H:i:s', $blockedUntil)
                : null,
        ],
        'queue' => [
            'pending_jobs' => DB::table('jobs')->where('queue', 'emails')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
        ]
    ]);
});

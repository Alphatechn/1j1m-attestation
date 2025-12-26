<?php
// emergency_send.php - ENVOI TRÈS LENT POUR HOSTINGER
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Participant;
use App\Services\AttestationService;

echo "🚨 MODE URGENCE - HOSTINGER RATE LIMIT 🚨\n";
echo "========================================\n\n";

// METS TES IDs ICI (maximum 5-10 à la fois)
$participantIds = [/* IDs RESTANTS */];

if (empty($participantIds)) {
    echo "❌ Aucun ID fourni.\n";
    exit;
}

echo "⚠️  ATTENTION: Hostinger est TRÈS strict\n";
echo "⚠️  Délai: 60 SECONDES entre chaque email\n";
echo "⚠️  Maximum: 5 emails cette heure\n\n";

$service = app(AttestationService::class);
$success = 0;

foreach ($participantIds as $index => $id) {
    $num = $index + 1;

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "📧 EMAIL #{$num} / " . count($participantIds) . "\n";
    echo str_repeat("=", 50) . "\n";

    $participant = Participant::find($id);
    if (!$participant) {
        echo "❌ Participant introuvable (ID: {$id})\n";
        continue;
    }

    echo "👤: " . $participant->full_name . "\n";
    echo "📧: " . $participant->email . "\n";

    // ⭐⭐ DÉLAI TRÈS LONG - 60 SECONDES ⭐⭐
    if ($index > 0) {
        echo "⏳ Délai sécurité: 60 secondes...\n";
        for ($i = 60; $i > 0; $i--) {
            if ($i % 10 == 0 || $i <= 5) {
                echo "   {$i}...\n";
            }
            sleep(1);
        }
        echo "✅ Délai terminé\n";
    }

    // Limiter à 5 emails max
    if ($success >= 5) {
        echo "🚫 LIMITE: Déjà 5 emails envoyés cette heure\n";
        echo "💡 Attendez l'heure prochaine pour les suivants\n";
        break;
    }

    try {
        echo "🔄 Création... ";
        $attestation = $service->createAttestation($participant, 1);
        echo "✅\n";
        echo "🎫 Numéro: " . $attestation->attestation_number . "\n";
        $success++;

    } catch (Exception $e) {
        echo "❌ ERREUR: " . $e->getMessage() . "\n";

        if (strpos($e->getMessage(), 'Trop d\'emails') !== false) {
            echo "💡 Message: " . $e->getMessage() . "\n";
            continue; // Passer au suivant
        }

        if (strpos($e->getMessage(), '451') !== false ||
            strpos($e->getMessage(), 'Ratelimit') !== false) {

            echo "\n🚨 🚨 🚨 CRITIQUE: HOSTINGER A BLOQUÉ 🚨 🚨 🚨\n";
            echo "⏰ Arrêt COMPLET pendant 2 HEURES\n";
            echo "🔧 Débloquer avec: php artisan email:unblock\n";
            break;
        }
    }
}

echo "\n\n" . str_repeat("=", 50) . "\n";
echo "📊 RÉSULTAT FINAL\n";
echo str_repeat("=", 50) . "\n";
echo "✅ Succès: {$success}\n";
echo "⏰ Prochain email possible dans: 60 secondes\n";
echo "💡 Recommendation: Attendre 1 heure avant nouvelle tentative\n";

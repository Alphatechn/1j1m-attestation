<?php

namespace App\Services;

use App\Models\Attestation;
use App\Models\Participant;
use TCPDF;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
class AttestationService
{
    // ✅ CONSTANTES UNIFIÉES
    const MIN_DELAY_SECONDS = 30;
    const MAX_EMAILS_PER_HOUR = 20; // Utilisé partout maintenant

    /**
     * Créer une attestation
     */
    public function createAttestation(Participant $participant, $userId = null)
    {
        // ✅ 1. LOCK ATOMIQUE (évite les race conditions)
        $lock = Cache::lock('email_send_lock', 35);

        if (!$lock->get()) {
            throw new \Exception("Un email est déjà en cours d'envoi. Veuillez patienter 30 secondes.");
        }

        try {
            // ✅ 2. Vérifications AVANT création
            $this->validateEmailQuota();
            $this->enforceMinimumDelay();

            // ✅ 3. Validation format email
            if (!filter_var($participant->email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("Format d'email invalide: {$participant->email}");
            }

            // ✅ 4. Créer l'attestation
            $attestation = Attestation::create([
                'participant_id' => $participant->id,
                'periode_id' => $participant->periode_id,
                'generated_by' => $userId,
                'issue_date' => Carbon::now(),
                'status' => 'pending',
                'content_text' => $this->generateContentText($participant),
            ]);

            // ✅ 5. Envoi email
            $this->sendAttestationByEmail($attestation);

            // ✅ 6. INCRÉMENTER APRÈS SUCCÈS UNIQUEMENT
            $this->incrementEmailCounters();

            return $attestation;

        } finally {
            // ✅ Toujours libérer le lock
            $lock->release();
        }
    }

        /**
     * ✅ CORRECTION: Vérification quota avec log détaillé
     */
    private function validateEmailQuota(): void
    {
        // Vérifier blocage Hostinger
        $blockedUntil = Cache::get('hostinger_blocked_until', 0);
        if ($blockedUntil > time()) {
            $waitMinutes = ceil(($blockedUntil - time()) / 60);
            throw new \Exception("Hostinger a bloqué les envois. Attendez {$waitMinutes} minutes.");
        }

        // Vérifier limite horaire
        $hourKey = 'email_count_hour_' . date('Y-m-d-H');
        $currentCount = Cache::get($hourKey, 0);

        if ($currentCount >= self::MAX_EMAILS_PER_HOUR) {
            $minutesLeft = 60 - (int)date('i');
            throw new \Exception("Limite horaire atteinte ({$currentCount}/" . self::MAX_EMAILS_PER_HOUR . "). Réessayez dans {$minutesLeft} minutes.");
        }

        Log::info("📊 Quota actuel: {$currentCount}/" . self::MAX_EMAILS_PER_HOUR);
    }

    /**
     * ✅ CORRECTION: Délai non-bloquant avec timestamp précis
     */
    private function enforceMinimumDelay(): void
    {
        $lastSent = Cache::get('last_email_sent_timestamp', 0);

        if ($lastSent > 0) {
            $elapsed = time() - $lastSent;
            $requiredWait = self::MIN_DELAY_SECONDS - $elapsed;

            if ($requiredWait > 0) {
                Log::info("⏳ Délai requis: {$requiredWait}s");
                throw new \Exception("Veuillez attendre {$requiredWait} secondes avant le prochain envoi.");
            }
        }
    }

    /**
     * ✅ CORRECTION: Incrémenter APRÈS succès uniquement
     */
    private function incrementEmailCounters(): void
    {
        $hourKey = 'email_count_hour_' . date('Y-m-d-H');
        Cache::increment($hourKey, 1, 3600);
        Cache::put('last_email_sent_timestamp', time(), 300);

        $newCount = Cache::get($hourKey, 0);
        Log::info("✅ Email envoyé. Nouveau compteur: {$newCount}/" . self::MAX_EMAILS_PER_HOUR);
    }

    /**
     * Envoyer l'attestation par email
     */

    public function sendAttestationByEmail(Attestation $attestation)
    {
        $participant = $attestation->participant;

        if (!$participant->email) {
            throw new \Exception("Le participant n'a pas d'adresse email.");
        }

        // ✅ Validation format
        if (!filter_var($participant->email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Format d'email invalide: {$participant->email}");
        }

        try {
            Log::info("📧 Envoi à: {$participant->email}");

            // Générer le PDF
            $pdfContent = $this->generatePDFOutput($attestation);
            $fileName = 'attestation_' . $attestation->attestation_number . '_' . $participant->full_name . '.pdf';

            // Envoyer l'email
            Mail::send('emails.attestation', [
                'participant' => $participant,
                'attestation' => $attestation,
                'periode' => $attestation->periode,
            ], function ($message) use ($participant, $pdfContent, $fileName) {
                $message->to($participant->email, $participant->full_name)
                    ->subject('Votre attestation de participation')
                    ->attachData($pdfContent, $fileName, [
                        'mime' => 'application/pdf',
                    ]);
            });

            // ✅ Mettre à jour statut
            $attestation->update([
                'status' => 'sent',
                'sent_at' => now(),
                'email_status' => 'success'
            ]);

            Log::info("✅ Email envoyé avec succès");
            return true;

        } catch (\Exception $e) {
            Log::error("❌ Échec envoi: " . $e->getMessage());

            // ✅ Enregistrer l'erreur
            $attestation->update([
                'email_status' => 'failed',
                'email_error' => substr($e->getMessage(), 0, 200)
            ]);

            // ✅ Détecter rate limit Hostinger
            if (str_contains($e->getMessage(), '451') ||
                str_contains($e->getMessage(), 'rate') ||
                str_contains($e->getMessage(), 'limit')) {

                Log::critical("🚨 RATE LIMIT HOSTINGER DÉTECTÉ");
                Cache::put('hostinger_blocked_until', time() + 3600, 3700);
            }

            throw $e;
        }
    }


    /**
     * Générer le contenu texte de l'attestation
     */
    public function generateContentText(Participant $participant)
    {
        $periode = $participant->periode;

        return "Je soussigné(e), certifie que " . $participant->full_name .
               " a participé à la formation/session organisée durant la période " .
               $periode->full_libelle . ". Cette attestation est délivrée pour servir " .
               "et valoir ce que de droit.";
    }

    /**
     * Générer le PDF de l'attestation
     */
    public function generatePDFOutput(Attestation $attestation)
    {
        define('TCPDF_FONTS_DIR', storage_path('all_font/'));
        $participant = $attestation->participant;
        $periode = $attestation->periode;

        /*
        |--------------------------------------------------------------------------
        | CRÉATION DU PDF
        |--------------------------------------------------------------------------
        */
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

        // Paramètres du document
        $pdf->SetCreator('Plateforme Attestations');
        $pdf->SetAuthor('Système de Gestion');
        $pdf->SetTitle('Attestation - ' . $attestation->attestation_number);
        $pdf->SetSubject('Attestation de participation');


        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->AddPage();

        /*
        |--------------------------------------------------------------------------
        | TEMPLATE (IMAGE DE FOND)
        |--------------------------------------------------------------------------
        */
        $pdf->Image(
            public_path('pdf/templates/attestation_1j1m.jpg'),
            0,
            0,
            297,
            210
        );


        /*
        |--------------------------------------------------------------------------
        | SIGNATURE IMAGE
        |--------------------------------------------------------------------------
        */
        $pdf->Image(
            public_path('pdf/templates/signature.png'),
            100,
            150,
            70,
            0,
            'PNG',
        );

                /*
        |--------------------------------------------------------------------------
        | CACHET IMAGE
        |--------------------------------------------------------------------------
        */
        $pdf->Image(
            public_path('pdf/templates/cachet.png'),
            190,
            135,
            50,
            0,
            'PNG',
        );



        // Numéro d'attestation
        $pdf->SetX(300);
        $pdf->SetY(20);
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(100, 5, 'N° ' . $attestation->attestation_number, 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(50);


        // Participant Name
        $pdf->SetY(62);
        $pdf->SetX(20);

        $maxWidth = 255; // largeur maximale du cadre
        $fontName = 'playfairdisplay';
        $fontStyle = 'B';
        $initialFontSize = 36;

        $participantName = mb_strtoupper($participant->full_name, 'UTF-8');
        $fontSize = $initialFontSize;

        // Boucle pour réduire la police si le texte est trop large
        while ($pdf->GetStringWidth($participantName, $fontName, $fontStyle, $fontSize) > $maxWidth && $fontSize > 8) {
            $fontSize--;
        }

        $pdf->SetFont($fontName, $fontStyle, $fontSize);

        // Cell centrée
        $pdf->Cell($maxWidth, 0, $participantName, 0, 1, 'C');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(10);

        // CODE QR
         $verificationUrl = route('public.attestations.verify', ['token' => $attestation->qr_token]);

        // Position du QR Code
        $qrX = 64;
        $qrY = 154;
        $qrSize = 30;

        // Générer le QR Code avec TCPDF (style='', pas de paramètre supplémentaire)
        $pdf->write2DBarcode(
            $verificationUrl,
            'QRCODE,H',  // Type et niveau de correction d'erreur
            $qrX,        // X position
            $qrY,        // Y position
            $qrSize,     // Width
            $qrSize,     // Height
            [
                'border' => false,
                'padding' => 0,
                'fgcolor' => [0, 0, 0],
                'bgcolor' => [255, 255, 255]
            ],
            'N'
        );

        $pdf->Ln(10);

        // Le responsable
        $pdf->SetY(150);
        $pdf->SetX(18);
        $pdf->SetFont('montserrat', 'u', 18);
        $pdf->Cell(259, 7, 'Le promoteur', 0, 1, 'C');

        $pdf->Ln(12);

        // Le responsable
        // $pdf->SetY(150);
        $pdf->SetX(18);
        $pdf->SetFont('montserrat', 'BI', 18);
        $pdf->Cell(259, 7, 'LIONEL TEJEM', 0, 1, 'C');


        // Date d'émission
        $pdf->SetY(180);
        $pdf->SetX(178);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(70, 7, 'Fait à Yaoundé le : ' . $attestation->issue_date->locale('fr')->isoFormat('DD MMMM YYYY'), 0, 1, 'C');

        /*
        |--------------------------------------------------------------------------
        | SORTIE DU PDF
        |--------------------------------------------------------------------------
        */
        return $pdf->Output('', 'S');
    }

    /**
     * Consulter une attestation (par numéro ou token)
     */
    public function viewAttestation($identifier, $type = 'number')
    {
        if ($type === 'token') {
            $attestation = Attestation::where('qr_token', $identifier)->first();
        } else {
            $attestation = Attestation::where('attestation_number', $identifier)->first();
        }

        if (!$attestation) {
            return null;
        }

        // Incrémenter le compteur de vues
        $attestation->increment('view_count');
        $attestation->update(['last_viewed_at' => now()]);

        return $attestation;
    }

    /**
     * Rechercher une attestation par nom du participant
     */
    public function searchByParticipantName($name)
    {
        return Attestation::whereHas('participant', function ($query) use ($name) {
            $query->where('name', 'LIKE', "%{$name}%");
        })->with(['participant', 'periode'])->get();
    }

    /**
     * Générer et télécharger le PDF
     */
    public function downloadPDF(Attestation $attestation)
    {
        $pdfContent = $this->generatePDFOutput($attestation);
        $fileName = 'attestation_' . $attestation->attestation_number . '.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    /**
     * Afficher le PDF dans le navigateur
     */
    public function displayPDF(Attestation $attestation)
    {
        $pdfContent = $this->generatePDFOutput($attestation);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="attestation_' . $attestation->attestation_number . '.pdf"');
    }
}

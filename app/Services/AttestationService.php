<?php

namespace App\Services;

use App\Models\Attestation;
use App\Models\Participant;
use TCPDF;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttestationService
{
    /**
     * Créer une attestation (sans générer le PDF)
     */
    public function createAttestation(Participant $participant, $userId = null)
    {
        $attestation = Attestation::create([
            'participant_id' => $participant->id,
            'periode_id' => $participant->periode_id,
            'generated_by' => $userId,
            'issue_date' => Carbon::now(),
            'status' => 'pending',
            'content_text' => $this->generateContentText($participant),
        ]);

        $this->sendAttestationByEmail($attestation);

        return $attestation;
    }

    /**
     * Générer le contenu texte de l'attestation
     */
    protected function generateContentText(Participant $participant)
    {
        $periode = $participant->periode;

        return "Je soussigné(e), certifie que " . $participant->full_name .
               " a participé à la formation/session organisée durant la période " .
               $periode->full_libelle . ". Cette attestation est délivrée pour servir " .
               "et valoir ce que de droit.";
    }

    /**
     * Générer le PDF à la volée (sans stockage)
     */
    // public function generatePDFOutput(Attestation $attestation)
    // {
    //     $participant = $attestation->participant;
    //     $periode = $attestation->periode;

    //     // Créer une instance TCPDF
    //     $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');

    //     // Paramètres du document
    //     $pdf->SetCreator('Plateforme Attestations');
    //     $pdf->SetAuthor('Système de Gestion');
    //     $pdf->SetTitle('Attestation - ' . $attestation->attestation_number);
    //     $pdf->SetSubject('Attestation de participation');

    //     // Marges
    //     $pdf->SetMargins(20, 20, 20);
    //     $pdf->SetAutoPageBreak(true, 20);

    //     // Supprimer header/footer par défaut
    //     $pdf->setPrintHeader(false);
    //     $pdf->setPrintFooter(false);

    //     // Ajouter une page
    //     $pdf->AddPage();

    //     // Logo ou En-tête (optionnel)
    //     // $pdf->Image('path/to/logo.png', 15, 10, 30);

    //     // Titre principal
    //     $pdf->SetFont('helvetica', 'B', 24);
    //     $pdf->SetTextColor(0, 51, 102); // Bleu foncé
    //     $pdf->Cell(0, 15, 'ATTESTATION', 0, 1, 'C');
    //     $pdf->SetFont('helvetica', '', 14);
    //     $pdf->Cell(0, 8, 'DE PARTICIPATION', 0, 1, 'C');

    //     // Ligne de séparation
    //     $pdf->SetLineWidth(0.5);
    //     $pdf->SetDrawColor(0, 51, 102);
    //     $pdf->Line(60, $pdf->GetY() + 5, 150, $pdf->GetY() + 5);
    //     $pdf->Ln(15);

    //     // Numéro d'attestation
    //     $pdf->SetFont('helvetica', 'I', 10);
    //     $pdf->SetTextColor(100, 100, 100);
    //     $pdf->Cell(0, 5, 'N° ' . $attestation->attestation_number, 0, 1, 'R');
    //     $pdf->SetTextColor(0, 0, 0);
    //     $pdf->Ln(10);

    //     // Contenu principal
    //     $pdf->SetFont('helvetica', '', 12);
    //     $pdf->MultiCell(0, 6, $attestation->content_text, 0, 'J', 0, 1);
    //     $pdf->Ln(15);

    //     // Encadré avec les informations
    //     $pdf->SetFillColor(240, 240, 240);
    //     $pdf->SetFont('helvetica', 'B', 11);
    //     $pdf->Cell(0, 8, 'INFORMATIONS DU PARTICIPANT', 0, 1, 'L', true);
    //     $pdf->Ln(5);

    //     // Informations participant
    //     $pdf->SetFont('helvetica', 'B', 11);
    //     $pdf->Cell(50, 7, 'Nom complet :', 0, 0);
    //     $pdf->SetFont('helvetica', '', 11);
    //     $pdf->Cell(0, 7, $participant->full_name, 0, 1);

    //     if ($participant->email) {
    //         $pdf->SetFont('helvetica', 'B', 11);
    //         $pdf->Cell(50, 7, 'Email :', 0, 0);
    //         $pdf->SetFont('helvetica', '', 11);
    //         $pdf->Cell(0, 7, $participant->email, 0, 1);
    //     }

    //     if ($participant->matricule) {
    //         $pdf->SetFont('helvetica', 'B', 11);
    //         $pdf->Cell(50, 7, 'Matricule :', 0, 0);
    //         $pdf->SetFont('helvetica', '', 11);
    //         $pdf->Cell(0, 7, $participant->matricule, 0, 1);
    //     }

    //     if ($participant->organisation) {
    //         $pdf->SetFont('helvetica', 'B', 11);
    //         $pdf->Cell(50, 7, 'Organisation :', 0, 0);
    //         $pdf->SetFont('helvetica', '', 11);
    //         $pdf->Cell(0, 7, $participant->organisation, 0, 1);
    //     }

    //     if ($participant->fonction) {
    //         $pdf->SetFont('helvetica', 'B', 11);
    //         $pdf->Cell(50, 7, 'Fonction :', 0, 0);
    //         $pdf->SetFont('helvetica', '', 11);
    //         $pdf->Cell(0, 7, $participant->fonction, 0, 1);
    //     }

    //     $pdf->Ln(10);

    //     // Période
    //     $pdf->SetFillColor(240, 240, 240);
    //     $pdf->SetFont('helvetica', 'B', 11);
    //     $pdf->Cell(0, 8, 'PÉRIODE DE FORMATION', 0, 1, 'L', true);
    //     $pdf->Ln(3);

    //     $pdf->SetFont('helvetica', '', 11);
    //     $pdf->Cell(0, 7, $periode->full_libelle, 0, 1);

    //     if ($periode->description) {
    //         $pdf->SetFont('helvetica', 'I', 9);
    //         $pdf->MultiCell(0, 5, $periode->description, 0, 'L');
    //     }

    //     $pdf->Ln(10);

    //     // Date d'émission
    //     $pdf->SetFont('helvetica', 'B', 11);
    //     $pdf->Cell(50, 7, 'Fait le :', 0, 0);
    //     $pdf->SetFont('helvetica', '', 11);
    //     $pdf->Cell(0, 7, $attestation->issue_date->locale('fr')->isoFormat('DD MMMM YYYY'), 0, 1);

    //     $pdf->Ln(15);

    //     // QR Code généré directement avec TCPDF
    //     $verificationUrl = route('public.attestations.verify', ['token' => $attestation->qr_token]);

    //     // Position du QR Code
    //     $qrX = 15;
    //     $qrY = $pdf->GetY();
    //     $qrSize = 35;

    //     // Générer le QR Code avec TCPDF (style='', pas de paramètre supplémentaire)
    //     $pdf->write2DBarcode(
    //         $verificationUrl,
    //         'QRCODE,H',  // Type et niveau de correction d'erreur
    //         $qrX,        // X position
    //         $qrY,        // Y position
    //         $qrSize,     // Width
    //         $qrSize,     // Height
    //         [
    //             'border' => false,
    //             'padding' => 0,
    //             'fgcolor' => [0, 0, 0],
    //             'bgcolor' => [255, 255, 255]
    //         ],
    //         'N'
    //     );

    //     // Texte à côté du QR Code
    //     $pdf->SetXY($qrX + $qrSize + 10, $qrY);
    //     $pdf->SetFont('helvetica', 'B', 10);
    //     $pdf->MultiCell(0, 5, "Vérification de l'attestation", 0, 'L');

    //     $pdf->SetXY($qrX + $qrSize + 10, $qrY + 8);
    //     $pdf->SetFont('helvetica', '', 9);
    //     $pdf->MultiCell(0, 4, "Scannez ce code QR ou visitez notre plateforme pour vérifier l'authenticité de cette attestation.", 0, 'L');

    //     $pdf->SetXY($qrX + $qrSize + 10, $qrY + 20);
    //     $pdf->SetFont('helvetica', 'I', 8);
    //     $pdf->SetTextColor(100, 100, 100);
    //     $pdf->MultiCell(0, 4, "URL: " . $verificationUrl, 0, 'L');

    //     // Pied de page avec signature (optionnel)
    //     $pdf->SetY(-40);
    //     $pdf->SetFont('helvetica', 'B', 10);
    //     $pdf->Cell(0, 7, 'Le Directeur / La Direction', 0, 1, 'R');

    //     // Retourner le PDF en string (pas de fichier créé)
    //     return $pdf->Output('', 'S');
    // }


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

        $participantName = $participant->full_name;
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
     * Envoyer l'attestation par email
     */
    public function sendAttestationByEmail(Attestation $attestation)
    {
        $participant = $attestation->participant;

        if (!$participant->email) {
            throw new \Exception("Le participant n'a pas d'adresse email.");
        }

        try {
            // Générer le PDF
            $pdfContent = $this->generatePDFOutput($attestation);
            $fileName = 'attestation_' . $attestation->attestation_number . '_'.$participant->full_name. '.pdf';

            // Envoyer l'email avec le PDF en pièce jointe
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

            // Mettre à jour le statut
            $attestation->update([
                'status' => 'sent',
                'sent_at' => now(),
                'email_status' => 'success'
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur envoi email attestation: ' . $e->getMessage());

            $attestation->update([
                'email_status' => 'failed'
            ]);

            throw $e;
        }
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

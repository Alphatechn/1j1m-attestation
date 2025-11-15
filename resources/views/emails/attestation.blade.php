<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attestation de Participation - 1Jeune1Metier</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #000000;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
        }

        .email-container {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            border: 2px solid #FFD700;
        }

        .header {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000000;
            padding: 30px 20px;
            text-align: center;
            border-bottom: 3px solid #000000;
        }

        .logo-container {
            max-width: 250px;
            margin: 0 auto 15px;
        }

        .logo-image {
            max-width: 100%;
            height: auto;
        }

        .header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            color: #000000;
        }

        .header-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin-top: 8px;
            font-weight: 500;
            color: #000000;
        }

        .content {
            background: #ffffff;
            padding: 30px;
            color: #000000;
        }

        .info-box {
            background: linear-gradient(135deg, #f5f5f5, #fff9e6);
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #FFA500;
            border-radius: 8px;
            border: 1px solid #FFD700;
        }

        .info-label {
            font-weight: bold;
            color: #B8860B;
            margin-bottom: 8px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #000000;
        }

        .button {
            display: inline-block;
            padding: 14px 35px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000000;
            text-decoration: none;
            border-radius: 10px;
            margin: 25px 0;
            font-weight: 700;
            border: 2px solid #000000;
            text-align: center;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .button:hover {
            background: linear-gradient(135deg, #FFA500, #B8860B);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #FFD700;
            color: #1a1a1a;
            font-size: 12px;
            background: #f5f5f5;
            padding: 25px 30px;
        }

        .contact-info {
            background: #ffffff;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #FFD700;
        }

        .contact-item {
            margin: 8px 0;
            color: #1a1a1a;
        }

        .contact-item strong {
            color: #000000;
        }

        .highlight {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            border: 1px solid #000000;
            color: #000000;
        }

        .highlight strong {
            color: #000000;
        }

        p {
            color: #000000;
        }

        strong {
            color: #000000;
        }

        @media (max-width: 600px) {
            body {
                padding: 10px;
            }

            .content {
                padding: 20px;
            }

            .header {
                padding: 20px 15px;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .button {
                display: block;
                margin: 20px auto;
                padding: 12px 25px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo-container">
                <!-- Remplacez par l'URL réelle de votre logo -->
                <img src="{{ $message->embed(public_path('assets/images/logo.png')) }}" alt="1Jeune1Metier" class="logo-image">
            </div>
            <h1>🎓 Attestation de Formation</h1>
            <div class="header-subtitle">Votre certification de formation</div>
        </div>

        <div class="content">
            <p>Bonjour <strong>{{ $participant->full_name }}</strong>,</p>

            <p>Nous avons le plaisir de vous adresser votre attestation de participation pour la période <strong>{{ $periode->full_libelle }}</strong>.</p>

            <div class="info-box">
                <div class="info-label">Numéro d'attestation :</div>
                <div class="info-value">{{ $attestation->attestation_number }}</div>
            </div>

            <div class="info-box">
                <div class="info-label">Date d'émission :</div>
                <div class="info-value">{{ $attestation->issue_date->format('d/m/Y') }}</div>
            </div>

            <div class="highlight">
                <p><strong>Votre attestation est jointe à cet email au format PDF.</strong></p>
                <p>Vous pouvez également la consulter à tout moment en scannant le code QR présent sur le document.</p>
            </div>

            <div style="text-align: center;">
                <a href="{{ route('public.attestations.verify', ['token' => $attestation->qr_token]) }}" class="button">
                    🔍 Vérifier mon attestation en ligne
                </a>
            </div>

            <div class="contact-info">
                <p style="margin: 0 0 10px 0; font-weight: bold; color: #000000;">📞 Besoin d'aide ?</p>
                <div class="contact-item">
                    <strong>Email :</strong> support@1jeune1metier.com
                </div>
                <div class="contact-item">
                    <strong>Téléphone :</strong> +237 6 91 63 26 40
                </div>
                <div class="contact-item">
                    <strong>Disponibilité :</strong> Lun-Ven: 9h-18h
                </div>
            </div>

            <p style="margin-top: 30px; text-align: center;">
                <strong>Cordialement,<br>L'équipe 1Jeune1Metier</strong>
            </p>
        </div>

        <div class="footer">
            <p><strong>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</strong></p>
            <p>© {{ date('Y') }} 1Jeune1Metier - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>

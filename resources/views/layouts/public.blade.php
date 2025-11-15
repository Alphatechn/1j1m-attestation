<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - CertiBot-1J1M</title>

    @include('layouts.inc.css')
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-gold: #FFD700;
            --secondary-gold: #FFA500;
            --dark-gold: #B8860B;
            --black: #000000;
            --dark-gray: #1a1a1a;
            --light-gray: #f5f5f5;
            --white: #ffffff;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--black) 0%, var(--dark-gray) 100%);
            min-height: 100vh;
            color: var(--black);
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
        }

        .public-container {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            overflow: hidden;
            border: 2px solid var(--primary-gold);
            margin: 2rem 0;
        }

        .header-section {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold));
            color: var(--black);
            padding: 2rem 2rem;
            text-align: center;
            border-bottom: 3px solid var(--black);
        }

        .logo-container {
            max-width: 300px;
            margin: 0 auto 1rem;
        }

        .logo-image {
            max-width: 100%;
            height: auto;
            filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.3));
        }

        .logo-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            color: var(--black);
            font-weight: 500;
            margin-top: 1rem;
        }

        .method-card {
            border: 2px solid var(--primary-gold);
            border-radius: 15px;
            transition: all 0.3s ease;
            background: var(--white);
            color: var(--black);
        }

        .method-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255, 215, 0, 0.2);
            border-color: var(--secondary-gold);
            background: linear-gradient(135deg, var(--white), #fff9e6);
        }

        .method-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--secondary-gold);
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .qr-scanner {
            max-width: 300px;
            margin: 0 auto;
            background: var(--light-gray);
            border-radius: 15px;
            padding: 2rem;
            border: 2px solid var(--primary-gold);
        }

        .attestation-card {
            border-left: 4px solid var(--secondary-gold);
            background: linear-gradient(135deg, var(--light-gray), #fff9e6);
            color: var(--black);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold));
            color: var(--black);
            border: 0px solid var(--black);
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 700;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, var(--secondary-gold), var(--dark-gold));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
            color: var(--black);
            border-color: var(--black);
        }

        .search-form {
            background: var(--white);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            border: 2px solid var(--primary-gold);
        }

        .footer-section {
            background: linear-gradient(135deg, var(--black), var(--dark-gray));
            color: var(--white);
            padding: 2rem 0;
            margin-top: auto;
            border-top: 3px solid var(--primary-gold);
        }

        .footer-help {
            background: var(--white);
            border-radius: 15px;
            padding: 2rem;
            border: 2px solid var(--primary-gold);
        }

        /* Text colors */
        .text-gold {
            color: var(--primary-gold) !important;
        }

        .text-black {
            color: var(--black) !important;
        }

        /* Background colors */
        .bg-gold {
            background-color: var(--primary-gold) !important;
        }

        .bg-black {
            background-color: var(--black) !important;
        }

        /* Additional styling for contrast */
        .card-title {
            color: var(--black);
            font-weight: 600;
        }

        .card-text {
            color: var(--dark-gray);
        }

        .form-control {
            border: 2px solid var(--primary-gold);
            border-radius: 8px;
            padding: 10px 15px;
        }

        .form-control:focus {
            border-color: var(--secondary-gold);
            box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
        }

        .footer-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header-section">
        <div class="container">
            <div class="logo-container">
                <img src="{{ asset('assets/images/logo.png') }}" alt="1Jeune1Metier" class="logo-image" onerror="this.style.display='none'; document.getElementById('textLogo').style.display='block';">
                <div id="textLogo" style="display: none;">
                    <div style="font-size: 2.5rem; font-weight: 700; color: var(--black);">1Jeune1Metier</div>
                </div>
            </div>
            <div class="logo-subtitle">@yield('page-subtitle', 'Vérification d\'Attestations de Formation')</div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer-section">
        <div class="container">
            <div class="footer-help">
                <h5 class="fw-bold mb-3 text-center text-black">
                    <i class="bi bi-question-circle me-2 text-gold"></i>Besoin d'aide ?
                </h5>
                <div class="row text-center">
                    <div class="col-md-4 mb-3">
                        <i class="bi bi-envelope footer-icon text-gold"></i>
                        <strong class="text-black d-block">Support Email</strong>
                        <small class="text-muted">support@1jeune1metier.com</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <i class="bi bi-telephone footer-icon text-gold"></i>
                        <strong class="text-black d-block">Assistance téléphonique</strong>
                        <small class="text-muted">+237 6 91 63 26 40</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <i class="bi bi-clock footer-icon text-gold"></i>
                        <strong class="text-black d-block">Disponibilité</strong>
                        <small class="text-muted">Lun-Ven: 9h-18h</small>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <small class="text-gold">&copy; 2025 CertiBot by 1J1M. Tous droits réservés.</small>
            </div>
        </div>
    </footer>

    @include('layouts.inc.js')
    @yield('scripts')
</body>
</html>

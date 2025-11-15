<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil | Plateforme d'Attestations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .welcome-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="welcome-card p-5 text-center">
                    <div class="mb-4">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="Logo" style="max-height: 80px;">
                    </div>

                    <h1 class="display-4 mb-3">Bienvenue</h1>
                    <p class="lead text-muted mb-4">
                        Plateforme de Gestion des Attestations
                    </p>

                    <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                        @auth
                            <a href="{{ route('dashboard.manage') }}" class="btn btn-primary btn-lg px-5">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-lg px-5">
                                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg px-5">
                                <i class="bi bi-box-arrow-in-right"></i> Se Connecter
                            </a>
                            <a href="{{ route('attestations.search') }}" class="btn btn-outline-primary btn-lg px-5">
                                <i class="bi bi-search"></i> Vérifier une attestation
                            </a>
                        @endauth
                    </div>

                    <hr class="my-5">

                    <div class="row text-center">
                        <div class="col-md-4 mb-3">
                            <div class="p-3">
                                <i class="bi bi-shield-check display-4 text-primary mb-3"></i>
                                <h5>Sécurisé</h5>
                                <p class="text-muted">Authentification et vérification par QR Code</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="p-3">
                                <i class="bi bi-lightning-charge display-4 text-warning mb-3"></i>
                                <h5>Rapide</h5>
                                <p class="text-muted">Génération instantanée des attestations</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="p-3">
                                <i class="bi bi-envelope display-4 text-success mb-3"></i>
                                <h5>Automatisé</h5>
                                <p class="text-muted">Envoi automatique par email</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-muted">
                        <small>© {{ date('Y') }} - Tous droits réservés</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

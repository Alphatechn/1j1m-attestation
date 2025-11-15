@extends('layouts.public')

@section('title', 'Vérification d\'Attestation')

@section('page-subtitle', 'Vérifiez et téléchargez vos attestations de formation')

@section('content')
<div class="public-container">
    <!-- Main Content -->
    <div class="container-fluid p-4">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="fw-bold text-dark mb-3">Comment vérifier votre attestation ?</h2>
                <p class="text-muted">Choisissez la méthode qui vous convient</p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Méthode 1: QR Code -->
            <div class="col-md-4">
                <div class="method-card text-center p-4 h-100">
                    <div class="method-icon">
                        <i class="bi bi-qr-code-scan"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Scan QR Code</h4>
                    <p class="text-muted mb-4">
                        Scannez le code QR présent sur votre attestation pour une vérification instantanée
                    </p>
                    <div class="qr-scanner mb-3">
                        <i class="bi bi-qr-code" style="font-size: 4rem; color: var(--secondary-gold);"></i>
                        <p class="small text-muted mt-2">Utilisez votre appareil photo</p>
                    </div>
                    <div class="alert alert-info small border-0" style="background: linear-gradient(135deg, #e3f2fd, #bbdefb);">
                        <i class="bi bi-info-circle me-1"></i>
                        Disponible sur l'attestation PDF
                    </div>
                </div>
            </div>

            <!-- Méthode 2: Recherche par Nom -->
            <div class="col-md-4">
                <div class="method-card text-center p-4 h-100">
                    <div class="method-icon">
                        <i class="bi bi-person-search"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Recherche par Nom</h4>
                    <p class="text-muted mb-4">
                        Recherchez avec votre nom et prénom pour trouver toutes vos attestations
                    </p>
                    <div class="text-muted mb-4">
                        <i class="bi bi-check-circle text-success me-1"></i>
                        Trouvez toutes vos attestations<br>
                        <i class="bi bi-check-circle text-success me-1"></i>
                        Téléchargez en un clic<br>
                        <i class="bi bi-check-circle text-success me-1"></i>
                        Consultation immédiate
                    </div>
                    <a href="{{ route('public.search-by-name') }}" class="btn btn-primary-custom w-100">
                        <i class="bi bi-search me-2"></i>Rechercher par Nom
                    </a>
                </div>
            </div>

            <!-- Méthode 3: Numéro d'Attestation -->
            <div class="col-md-4">
                <div class="method-card text-center p-4 h-100">
                    <div class="method-icon">
                        <i class="bi bi-file-text"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Numéro d'Attestation</h4>
                    <p class="text-muted mb-4">
                        Entrez directement le numéro de votre attestation pour la consulter
                    </p>
                    <div class="text-muted mb-4">
                        <i class="bi bi-lightning-charge text-warning me-1"></i>
                        Accès direct et rapide<br>
                        <i class="bi bi-shield-check text-primary me-1"></i>
                        Vérification sécurisée<br>
                        <i class="bi bi-download text-success me-1"></i>
                        Téléchargement instantané
                    </div>
                    <a href="{{ route('public.search-by-number') }}" class="btn btn-primary-custom w-100">
                        <i class="bi bi-hash me-2"></i>Rechercher par Numéro
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

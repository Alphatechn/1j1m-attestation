@extends('layouts.public')

@section('title', 'Vérification QR Code')

@section('content')
<div class="public-container">
    <div class="hero-section">
        <div class="logo">1Jeune1Metier</div>
        <div class="logo-subtitle">Vérification par QR Code</div>
    </div>

    <div class="container-fluid p-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                @if($valid)
                    <div class="alert alert-success text-center">
                        <i class="bi bi-check-circle-fill me-2" style="font-size: 1.5rem;"></i>
                        <strong style="font-size: 1.2rem;">Attestation Valide</strong>
                    </div>

                    <div class="attestation-card p-4 rounded">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="fw-bold text-dark mb-3">Détails de l'Attestation</h4>

                                <div class="mb-3">
                                    <strong class="text-primary">Participant:</strong><br>
                                    {{ $participant->first_name ? $participant->first_name . ' ' . $participant->last_name : $participant->last_name }}
                                </div>

                                <div class="mb-3">
                                    <strong class="text-primary">Période de formation:</strong><br>
                                    {{ $periode->libelle }}
                                </div>

                                <div class="mb-3">
                                    <strong class="text-primary">Numéro d'attestation:</strong><br>
                                    <code class="text-dark">{{ $attestation->attestation_number }}</code>
                                </div>

                                <div class="mb-3">
                                    <strong class="text-primary">Date d'émission:</strong><br>
                                    {{ $attestation->issue_date->format('d/m/Y') }}
                                </div>

                                @if($participant->organisation)
                                <div class="mb-3">
                                    <strong class="text-primary">Organisation:</strong><br>
                                    {{ $participant->organisation }}
                                </div>
                                @endif
                            </div>

                            <div class="col-md-4 text-center">
                                <div class="bg-light p-3 rounded">
                                    <i class="bi bi-qr-code" style="font-size: 6rem; color: #2563eb;"></i>
                                    <p class="small text-muted mt-2">QR Code vérifié</p>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12 text-center">
                                <a href="{{ route('attestations.download', $attestation->id) }}"
                                   class="btn btn-primary-custom me-3" target="_blank">
                                    <i class="bi bi-download me-2"></i>Télécharger l'Attestation
                                </a>
                                <a href="{{ url('/') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-house me-2"></i>Retour à l'accueil
                                </a>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-danger text-center">
                        <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 1.5rem;"></i>
                        <strong style="font-size: 1.2rem;">QR Code Invalide</strong>
                    </div>

                    <div class="text-center p-5">
                        <i class="bi bi-qr-code-scan" style="font-size: 4rem; color: #dc3545;"></i>
                        <h4 class="text-dark mt-3">Code QR non reconnu</h4>
                        <p class="text-muted">{{ $message }}</p>

                        <div class="mt-4">
                            <a href="{{ url('/') }}" class="btn btn-primary-custom">
                                <i class="bi bi-arrow-left me-2"></i>Retour à l'accueil
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

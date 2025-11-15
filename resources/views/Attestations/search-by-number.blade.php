@extends('layouts.public')

@section('title', 'Recherche par Numéro')

@section('content')
<div class="public-container">

    <div class="container-fluid p-4">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <!-- Formulaire de recherche -->
                <div class="method-card p-4 mb-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-hash method-icon"></i>
                        <h3 class="fw-bold text-dark mb-2">Recherche par Numéro</h3>
                        <p class="text-muted">Entrez le numéro de votre attestation pour la consulter</p>
                    </div>

                    <form id="searchForm" class="search-form">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label fw-bold">Numéro d'Attestation <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg"
                                   name="attestation_number"
                                   placeholder="Ex: AT-2024-00123 ou AT202400123"
                                   required minlength="5" maxlength="50">
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Le numéro se trouve en haut de votre attestation
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary-custom btn-lg w-100" id="searchBtn">
                                <i class="bi bi-search me-2"></i>Vérifier l'attestation
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Résultat -->
                <div id="searchResult" style="display: none;">
                    <div class="method-card p-4">
                        <h4 class="fw-bold text-dark mb-3">Attestation Trouvée</h4>
                        <div id="resultContainer"></div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="text-center mt-4">
                    <a href="{{ route('public.home') }}" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left me-2"></i>Retour à l'accueil
                    </a>
                    <a href="{{ route('public.search-by-name') }}" class="btn btn-outline-primary">
                        <i class="bi bi-person me-2"></i>Rechercher par nom
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Soumission du formulaire
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();

        const attestationNumber = $('input[name="attestation_number"]').val().trim();

        if (attestationNumber.length < 5) {
            showError('Le numéro d\'attestation doit contenir au moins 5 caractères');
            return;
        }

        performSearch(attestationNumber);
    });

    function performSearch(attestationNumber) {
        $('#searchBtn').prop('disabled', true)
            .html('<i class="bi bi-hourglass-split me-2"></i>Vérification en cours...');

        $('#searchResult').hide();

        $.ajax({
            url: '{{ route("public.search-by-number.post") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                attestation_number: attestationNumber
            },
            success: function(response) {
                $('#searchBtn').prop('disabled', false)
                    .html('<i class="bi bi-search me-2"></i>Vérifier l\'attestation');

                if (response.success) {
                    displayResult(response.data);
                }
            },
            error: function(xhr) {
                $('#searchBtn').prop('disabled', false)
                    .html('<i class="bi bi-search me-2"></i>Vérifier l\'attestation');

                let errorMessage = 'Aucune attestation trouvée avec ce numéro';
                if (xhr.status === 422) {
                    // Récupérer le premier message d'erreur de validation
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors)[0][0];
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                showError(errorMessage);
            }
        });
    }

    function displayResult(attestation) {
    const participant = attestation.participant;
    const fullName = participant.name;

    const resultHtml = `
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Attestation valide et vérifiée</strong>
        </div>

        <div class="row mb-3">
            <div class="col-sm-6">
                <strong>Nom complet:</strong><br>
                ${fullName}
            </div>
            <div class="col-sm-6">
                <strong>Numéro:</strong><br>
                <code>${attestation.attestation_number}</code>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-sm-6">
                <strong>Période:</strong><br>
                ${attestation.periode.libelle}
            </div>
            <div class="col-sm-6">
                <strong>Date d'émission:</strong><br>
                ${new Date(attestation.issue_date).toLocaleDateString('fr-FR')}
            </div>
        </div>

        ${participant.organisation ? `
        <div class="row mb-3">
            <div class="col-12">
                <strong>Organisation:</strong><br>
                ${participant.organisation}
            </div>
        </div>
        ` : ''}

        <div class="text-center mt-4">
            <a href="{{ url('public-download') }}/${attestation.id}"
                class="btn btn-success btn-sm" target="_blank">
                <i class="bi bi-download me-1"></i>Télécharger
            </a>
            <a href="{{ url('public-preview') }}/${attestation.id}"
                class="btn btn-info btn-sm" target="_blank">
                <i class="bi bi-file-pdf me-1"></i>Visualiser
            </a>
        </div>
    `;

    $('#resultContainer').html(resultHtml);
    $('#searchResult').show();
}

    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Attestation non trouvée',
            text: message,
            confirmButtonColor: '#2563eb'
        });
    }

    // Auto-focus sur le champ de recherche
    $('input[name="attestation_number"]').focus();

    // Validation en temps réel
    $('input[name="attestation_number"]').on('input', function() {
        const attestationNumber = $(this).val().trim();
        const searchBtn = $('#searchBtn');

        if (attestationNumber.length >= 5) {
            searchBtn.prop('disabled', false)
                .removeClass('btn-secondary')
                .addClass('btn-primary-custom');
        } else {
            searchBtn.prop('disabled', true)
                .removeClass('btn-primary-custom')
                .addClass('btn-secondary');
        }
    });

    // Entrée pour soumettre le formulaire
    $('input[name="attestation_number"]').on('keypress', function(e) {
        if (e.which === 13) { // Touche Entrée
            e.preventDefault();
            if ($(this).val().trim().length >= 5) {
                $('#searchForm').submit();
            }
        }
    });
});
</script>
@endsection

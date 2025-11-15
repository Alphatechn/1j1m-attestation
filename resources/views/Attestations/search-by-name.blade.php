@extends('layouts.public')

@section('title', 'Recherche par Nom')

@section('content')
<div class="public-container">

    <div class="container-fluid p-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Formulaire de recherche -->
                <div class="method-card p-4 mb-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-search method-icon"></i>
                        <h3 class="fw-bold text-dark mb-2">Recherche par Nom</h3>
                        <p class="text-muted">Entrez votre nom et prénom pour trouver vos attestations</p>
                    </div>

                    <form id="searchForm" class="search-form">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Votre Nom complet <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg"
                                       name="name" placeholder="Ex: Dupont" required
                                       minlength="2" maxlength="50">
                                <div class="form-text">Minimum 2 caractères</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center mt-4 px-2">
                            <button type="submit" class="btn btn-primary-custom " id="searchBtn" style="max-width: 250px;">
                                <i class="bi bi-search me-2"></i>Rechercher
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Résultats -->
                <div id="searchResults" style="display: none;">
                    <div class="method-card p-4">
                        <h4 class="fw-bold text-dark mb-3" id="resultsTitle"></h4>
                        <div id="resultsContainer"></div>
                    </div>
                </div>

                <!-- Aucun résultat -->
                <div id="noResults" class="text-center" style="display: none;">
                    <div class="method-card p-4">
                        <i class="bi bi-search-x method-icon text-warning"></i>
                        <h4 class="fw-bold text-dark mb-3">Aucune attestation trouvée</h4>
                        <p class="text-muted mb-4" id="noResultsMessage"></p>
                        <button class="btn btn-outline-primary" onclick="resetSearch()">
                            <i class="bi bi-arrow-repeat me-2"></i>Nouvelle recherche
                        </button>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="text-center mt-4">
                    <a href="{{ route('public.home') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Retour à l'accueil
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
        performSearch(this); // Passer 'this' en paramètre
    });

    function performSearch(formElement) {
        const formData = new FormData(formElement); // Utiliser formElement
        const name = $('input[name="name"]').val().trim();

        // Validation côté client
        if (name.length < 2) {
            showError('Veuillez saisir au moins 2 caractères pour le nom complet');
            return;
        }

        // État de chargement
        $('#searchBtn').prop('disabled', true)
            .html('<i class="bi bi-hourglass-split me-2"></i>Recherche en cours...');

        // Masquer les résultats précédents
        $('#searchResults').hide();
        $('#noResults').hide();

        // Requête AJAX - Utiliser les données directement au lieu de FormData
        $.ajax({
            url: '{{ route("public.search-by-name.post") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                name: name
            },
            success: function(response) {
                $('#searchBtn').prop('disabled', false)
                    .html('<i class="bi bi-search me-2"></i>Rechercher mes attestations');

                if (response.success) {
                    if (response.count > 0) {
                        displayResults(response.data, response.message);
                    } else {
                        showNoResults(response.message);
                    }
                }
            },
            error: function(xhr) {
                $('#searchBtn').prop('disabled', false)
                    .html('<i class="bi bi-search me-2"></i>Rechercher mes attestations');

                let errorMessage = 'Une erreur est survenue';
                if (xhr.status === 422) {
                    // Récupérer le premier message d'erreur
                    const errors = xhr.responseJSON.errors;
                    const firstError = Object.values(errors)[0][0];
                    errorMessage = firstError;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                showError(errorMessage);
            }
        });
    }

    function displayResults(attestations, message) {
        let resultsHtml = '';

        if (attestations && attestations.length > 0) {
            attestations.forEach((attestation, index) => {
                const participant = attestation.participant;
                const fullName = participant.name;

                resultsHtml += `
                    <div class="attestation-card p-3 mb-3 rounded">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="fw-bold text-primary mb-2">${fullName}</h5>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="mb-1">
                                            <i class="bi bi-hash text-muted me-2"></i>
                                            <strong>N°:</strong> ${attestation.attestation_number}
                                        </p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="mb-1">
                                            <i class="bi bi-calendar text-muted me-2"></i>
                                            <strong>Période:</strong> ${attestation.periode.libelle}
                                        </p>
                                    </div>
                                </div>
                                <p class="mb-0 text-muted small">
                                    <i class="bi bi-clock me-1"></i>
                                    Émise le ${new Date(attestation.issue_date).toLocaleDateString('fr-FR')}
                                </p>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="d-grid gap-2">
                                    <a href="{{ url('public-download') }}/${attestation.id}"
                                        class="btn btn-success btn-sm" target="_blank">
                                        <i class="bi bi-download me-1"></i>Télécharger
                                    </a>
                                    <a href="{{ url('public-preview') }}/${attestation.id}"
                                        class="btn btn-info btn-sm" target="_blank">
                                        <i class="bi bi-file-pdf me-1"></i>Visualiser
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        } else {
            resultsHtml = '<div class="alert alert-warning">Aucune attestation trouvée</div>';
        }

        $('#resultsTitle').text(message);
        $('#resultsContainer').html(resultsHtml);
        $('#searchResults').show();
    }

    function showNoResults(message) {
        $('#noResultsMessage').text(message);
        $('#noResults').show();
    }

    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: message,
            confirmButtonColor: '#2563eb'
        });
    }

    function resetSearch() {
        $('#searchForm')[0].reset();
        $('#searchResults').hide();
        $('#noResults').hide();
        $('input[name="first_name"]').focus();
    }

    // Auto-focus
    $('input[name="first_name"]').focus();

    // Validation en temps réel pour activer/désactiver le bouton
    $('input[name="name"]').on('input', function() {
        const name = $('input[name="name"]').val().trim();
        const searchBtn = $('#searchBtn');

        if (name.length >= 2) {
            searchBtn.prop('disabled', false).removeClass('btn-secondary').addClass('btn-primary-custom');
        } else {
            searchBtn.prop('disabled', true).removeClass('btn-primary-custom').addClass('btn-secondary');
        }
    });
});
</script>
@endsection

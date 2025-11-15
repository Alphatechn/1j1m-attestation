@extends('layouts.front')

@section('title', 'Attestations')

@section('content')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Gestion des Attestations</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Attestations</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row">

                <!-- Modal Spinner Global -->
                <div class="modal fade" id="spinnerModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-transparent border-0">
                            <div class="modal-body text-center">
                                <div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p class="text-light mt-2">Chargement en cours...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Génération Individuelle -->
                <div class="modal fade" id="singleGenerateModal" tabindex="-1" aria-labelledby="singleGenerateModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="singleGenerateModalLabel">
                                    <i class="bi bi-person-plus"></i> Générer une Attestation Individuelle
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="singleGenerateForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Période <span class="text-danger">*</span></label>
                                            <select id="single_periode_id" name="periode_id" class="form-select" required>
                                                <option value="">Sélectionnez une période</option>
                                                @foreach($periodes as $periode)
                                                    <option value="{{ $periode->id }}">{{ $periode->libelle }}</option>
                                                @endforeach
                                            </select>
                                            <span id="error_single_periode_id" class="text-danger small"></span>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Participant <span class="text-danger">*</span></label>
                                            <select id="single_participant_id" name="participant_id" class="form-select select2-participant" style="border: 1px solid #070e15ff;" required disabled>
                                                <option value="">Choisissez d'abord une période</option>
                                            </select>
                                            <span id="error_single_participant_id" class="text-danger small"></span>
                                            <div class="form-text">
                                                Commencez à taper pour rechercher un participant par nom, prénom ou email
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Options</label>
                                        <div class="form-check">
                                            <input type="checkbox" id="single_send_email" name="send_email" class="form-check-input" checked>
                                            <label class="form-check-label" for="single_send_email">
                                                Envoyer automatiquement par email
                                            </label>
                                        </div>
                                        <div class="form-text">
                                            Si coché, l'attestation sera envoyée par email au participant.
                                            Nécessite que le participant ait une adresse email valide.
                                        </div>
                                    </div>

                                    <div id="participantInfo" class="alert alert-info d-none">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <strong><i class="bi bi-person"></i> Nom:</strong>
                                                <span id="participantName">-</span>
                                            </div>
                                            <div class="col-md-4">
                                                <strong><i class="bi bi-envelope"></i> Email:</strong>
                                                <span id="participantEmail">-</span>
                                            </div>
                                            <div class="col-md-4">
                                                <strong><i class="bi bi-building"></i> Organisation:</strong>
                                                <span id="participantOrganisation">-</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="existingAttestationAlert" class="alert alert-warning d-none">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <strong>Attention:</strong> Ce participant a déjà une attestation pour cette période.
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="button" class="btn btn-primary" id="confirmSingleGenerateBtn">
                                    <i class="bi bi-file-plus"></i> Générer l'Attestation
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Génération en Masse -->
                <div class="modal fade" id="bulkGenerateModal" tabindex="-1" aria-labelledby="bulkGenerateModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title" id="bulkGenerateModalLabel">
                                    <i class="bi bi-gear"></i> Génération d'Attestations en Masse
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="bulkGenerateForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label">Période <span class="text-danger">*</span></label>
                                        <select id="bulk_periode_id" name="periode_id" class="form-select" required>
                                            <option value="">Sélectionnez une période</option>
                                            @foreach($periodes as $periode)
                                                <option value="{{ $periode->id }}">{{ $periode->libelle }}</option>
                                            @endforeach
                                        </select>
                                        <span id="error_bulk_periode_id" class="text-danger small"></span>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Options</label>
                                        <div class="form-check">
                                            <input type="checkbox" id="send_emails" name="send_emails" class="form-check-input" checked>
                                            <label class="form-check-label" for="send_emails">
                                                Envoyer automatiquement par email
                                            </label>
                                        </div>
                                        <div class="form-text">
                                            Si coché, les attestations seront envoyées par email aux participants.
                                            Seuls les participants avec une adresse email valide recevront l'attestation.
                                        </div>
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        <strong>Information:</strong> Cette action va générer des attestations pour tous les participants
                                        de la période sélectionnée qui n'ont pas encore d'attestation.
                                    </div>

                                    <div id="participantsInfo" class="alert alert-warning d-none">
                                        <i class="bi bi-people"></i>
                                        <strong id="participantsCount">0</strong> participant(s) seront traités.
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="button" class="btn btn-success" id="confirmBulkGenerateBtn">
                                    <i class="bi bi-play-circle"></i> Lancer la Génération
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Détails Attestation -->
                <div class="modal fade" id="attestationViewModal" tabindex="-1" aria-labelledby="attestationViewModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-info text-white">
                                <h5 class="modal-title" id="attestationViewModalLabel">
                                    <i class="bi bi-file-text"></i> Détails de l'Attestation
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <table class="table table-borderless">
                                            <tr>
                                                <th width="30%">Numéro:</th>
                                                <td><span id="view_attestation_number" class="fw-bold"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Participant:</th>
                                                <td><span id="view_participant_name" class="fw-bold"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Période:</th>
                                                <td><span id="view_periode"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Email participant:</th>
                                                <td><span id="view_participant_email"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Statut:</th>
                                                <td><span id="view_status"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Date d'émission:</th>
                                                <td><span id="view_issue_date"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Envoyée le:</th>
                                                <td><span id="view_sent_at"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Générée par:</th>
                                                <td><span id="view_generated_by"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Nombre de vues:</th>
                                                <td><span id="view_view_count" class="badge bg-primary"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Dernière consultation:</th>
                                                <td><span id="view_last_viewed_at"></span></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Suppression -->
                <div class="modal fade" id="attestationDeleteModal" tabindex="-1" aria-labelledby="attestationDeleteModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="attestationDeleteModalLabel">
                                    <i class="bi bi-trash"></i> Confirmer la Suppression
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="delete_attestation_id">
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Attention!</strong> Cette action est irréversible.
                                </div>
                                <p class="mb-0">Voulez-vous vraiment supprimer cette attestation ?</p>
                                <p class="text-muted small">L'attestation sera définitivement supprimée du système.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                                    <i class="bi bi-trash"></i> Oui, Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="col-12 mb-4">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h4 class="mb-0" id="stats_total">0</h4>
                                    <small>Total Attestations</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4 class="mb-0" id="stats_sent">0</h4>
                                    <small>Envoyées</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h4 class="mb-0" id="stats_pending">0</h4>
                                    <small>En attente</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h4 class="mb-0" id="stats_views">0</h4>
                                    <small>Total Consultations</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center">
                                    <h4 class="mb-0" id="stats_this_month">0</h4>
                                    <small>Ce mois-ci</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des Attestations -->
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="bi bi-file-text"></i> Liste des Attestations
                            </h4>
                            <div>
                                <button class="btn btn-light btn-sm me-2" data-bs-toggle="modal" data-bs-target="#singleGenerateModal">
                                    <i class="bi bi-person-plus"></i> Générer une Attestation
                                </button>
                                <button class="btn btn-light btn-sm me-2" data-bs-toggle="modal" data-bs-target="#bulkGenerateModal">
                                    <i class="bi bi-gear"></i> Génération en Masse
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filtres -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Filtrer par période:</label>
                                    <select id="filter_periode" class="form-select form-select-sm">
                                        <option value="">Toutes les périodes</option>
                                        @foreach($periodes as $periode)
                                            <option value="{{ $periode->id }}">{{ $periode->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Statut:</label>
                                    <select id="filter_status" class="form-select form-select-sm">
                                        <option value="">Tous les statuts</option>
                                        <option value="pending">En attente</option>
                                        <option value="sent">Envoyée</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Recherche:</label>
                                    <input type="text" id="filter_search" class="form-control form-control-sm"
                                           placeholder="Numéro, nom participant...">
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-bordered" id="attestationsTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="5%">N°</th>
                                            <th width="15%">Numéro Attestation</th>
                                            <th width="20%">Participant</th>
                                            <th width="15%">Période</th>
                                            <th width="10%" class="text-center">Statut</th>
                                            <th width="15%" class="text-center">Date Émission</th>
                                            <th width="20%" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Les données seront chargées par DataTables -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('styles')

<style>
.stats-card {
    transition: transform 0.2s;
}
.stats-card:hover {
    transform: translateY(-2px);
}
/* Personnalisation Select2 */
.select2-container--bootstrap-5 .select2-selection {
    min-height: 38px;
    padding: 4px;
    border: 1px solid #ced4da;
}
</style>
@endsection

@section('scripts')

<script>
/**
 * Gestion des Attestations - CRUD avec DataTables
 */

$(document).ready(function() {
    // Variables globales
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    let dataTable;

    // Configuration globale pour AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    // Initialisation de DataTable
    function initializeDataTable() {
        dataTable = $('#attestationsTable').DataTable({
            processing: true,
            serverSide: false,
            responsive: true,
            ajax: {
                url: '/attestations',
                type: 'GET',
                dataSrc: function (json) {
                    if (json.success && json.data) {
                        return json.data.data || json.data;
                    }
                    return [];
                },
                error: function(xhr, error, thrown) {
                    console.error('Erreur AJAX:', xhr);
                    let errorMessage = 'Erreur lors du chargement des données';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    $('#attestationsTable tbody').html(
                        `<tr><td colspan="7" class="text-center text-danger">${errorMessage}</td></tr>`
                    );
                }
            },
            columns: [
                {
                    data: null,
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    },
                    className: 'text-center',
                    orderable: false
                },
                {
                    data: 'attestation_number',
                    render: function(data) {
                        return `<code class="text-primary">${data}</code>`;
                    }
                },
                {
                    data: 'participant',
                    render: function(data) {
                        if (!data) return '<span class="text-muted">N/A</span>';

                        const fullName = data.name;

                        let info = `<strong>${fullName}</strong>`;
                        if (data.email) {
                            info += `<br><small class="text-muted">${data.email}</small>`;
                        }
                        return info;
                    }
                },
                {
                    data: 'periode',
                    render: function(data) {
                        return data ? data.libelle : '<span class="text-muted">N/A</span>';
                    }
                },
                {
                    data: 'status',
                    render: function(data) {
                        if (data === 'sent') {
                            return '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Envoyée</span>';
                        } else {
                            return '<span class="badge bg-warning"><i class="bi bi-clock"></i> En attente</span>';
                        }
                    },
                    className: 'text-center'
                },
                {
                    data: 'issue_date',
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString('fr-FR') : '-';
                    },
                    className: 'text-center'
                },
                {
                    data: 'id',
                    render: function(data, type, row) {
                        const canSendEmail = row.participant && row.participant.email && row.status !== 'sent';

                        return `
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-info view-btn" data-id="${data}" title="Détails">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a href="/attestations/${data}/download" class="btn btn-primary" title="Télécharger PDF" target="_blank">
                                    <i class="bi bi-download"></i>
                                </a>
                                <a href="/attestations/${data}/preview" class="btn btn-secondary" title="Visualiser PDF" target="_blank">
                                    <i class="bi bi-file-pdf"></i>
                                </a>
                                ${canSendEmail ? `
                                    <button class="btn btn-success send-email-btn" data-id="${data}" title="Envoyer par email">
                                        <i class="bi bi-envelope"></i>
                                    </button>
                                ` : ''}
                                <button class="btn btn-danger delete-btn" data-id="${data}" title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        `;
                    },
                    className: 'text-center',
                    orderable: false
                }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
            },
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[5, 'desc']] // Tri par date d'émission décroissante
        });

        // Appliquer les filtres
        $('#filter_periode, #filter_status').on('change', function() {
            applyFilters();
        });

        $('#filter_search').on('keyup', function() {
            dataTable.search(this.value).draw();
        });
    }

    // Initialiser DataTable
    initializeDataTable();

    initializeParticipantSelect();

    // Charger les statistiques
    loadStats();

    // ============= FONCTIONS UTILITAIRES =============

    function showSpinner() {
        $('#spinnerModal').modal('show');
    }

    function hideSpinner() {
        setTimeout(() => $('#spinnerModal').modal('hide'), 500);
    }

    function clearErrors() {
        $('[id^="error_"]').text('');
        $('.is-invalid').removeClass('is-invalid');
    }

    function showToast(message, type = 'success') {
        const icon = type === 'success' ? 'success' : 'error';
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon,
            title: message,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }

    function applyFilters() {
        const periode = $('#filter_periode').val();
        const status = $('#filter_status').val();

        let url = '/attestations?';
        const params = [];

        if (periode) params.push(`periode_id=${periode}`);
        if (status) params.push(`status=${status}`);

        if (params.length > 0) {
            url += params.join('&');
        }

        dataTable.ajax.url(url).load();
    }

    function loadStats() {
        $.ajax({
            url: '/attestations/stats/global',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const stats = response.data;
                    $('#stats_total').text(stats.total);
                    $('#stats_sent').text(stats.sent);
                    $('#stats_pending').text(stats.pending);
                    $('#stats_views').text(stats.total_views);
                    $('#stats_this_month').text(stats.this_month);
                }
            },
            error: function(xhr) {
                console.error('Erreur chargement stats:', xhr);
            }
        });
    }

    // ============= GÉNÉRATION EN MASSE =============

    // Charger le nombre de participants quand la période change
    $('#bulk_periode_id').on('change', function() {
        const periodeId = $(this).val();

        if (!periodeId) {
            $('#participantsInfo').addClass('d-none');
            return;
        }

        showSpinner();

        $.ajax({
            url: `/participants/without-attestation/list?periode_id=${periodeId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const count = response.data.total || response.data.length || 0;
                    $('#participantsCount').text(count);

                    if (count > 0) {
                        $('#participantsInfo').removeClass('d-none');
                    } else {
                        $('#participantsInfo').addClass('d-none');
                        $('#participantsInfo').html(`
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Aucun participant</strong> sans attestation trouvé pour cette période.
                        `).removeClass('alert-warning').addClass('alert-danger');
                    }
                }
                hideSpinner();
            },
            error: function(xhr) {
                hideSpinner();
                $('#participantsInfo').addClass('d-none');
            }
        });
    });

    $('#confirmBulkGenerateBtn').on('click', function() {
        const periodeId = $('#bulk_periode_id').val();
        const sendEmails = $('#send_emails').is(':checked');

        if (!periodeId) {
            showToast('Veuillez sélectionner une période', 'error');
            return;
        }

        showSpinner();

        // Récupérer les participants sans attestation
        $.ajax({
            url: `/participants/without-attestation/list?periode_id=${periodeId}`,
            method: 'GET',
            success: function(response) {
                if (response.success && response.data.data && response.data.data.length > 0) {
                    startBulkGeneration(response.data.data, sendEmails);
                } else {
                    hideSpinner();
                    showToast('Aucun participant sans attestation trouvé pour cette période', 'warning');
                }
            },
            error: function(xhr) {
                hideSpinner();
                showToast('Erreur lors de la récupération des participants', 'error');
            }
        });
    });

    function startBulkGeneration(participants, sendEmails) {
        let processed = 0;
        let successCount = 0;
        let errorCount = 0;
        const total = participants.length;

        // Fonction récursive pour traiter les participants un par un
        function processNext() {
            if (processed >= total) {
                hideSpinner();
                $('#bulkGenerateModal').modal('hide');

                let message = `Génération terminée: ${successCount} attestation(s) créée(s)`;
                if (errorCount > 0) {
                    message += `, ${errorCount} erreur(s)`;
                }

                Swal.fire({
                    icon: errorCount > 0 ? 'warning' : 'success',
                    title: 'Génération en masse terminée',
                    html: `
                        <p>${message}</p>
                        <div class="progress mt-3">
                            <div class="progress-bar bg-success" style="width: ${(successCount/total)*100}%"></div>
                            <div class="progress-bar bg-danger" style="width: ${(errorCount/total)*100}%"></div>
                        </div>
                    `,
                    confirmButtonText: 'OK'
                });

                // Recharger le tableau
                dataTable.ajax.reload();
                loadStats();
                return;
            }

            const participant = participants[processed];
            processed++;

            // Mettre à jour la progression
            updateProgress(processed, total, successCount, errorCount);

            // Créer l'attestation
            $.ajax({
                url: '/attestations',
                method: 'POST',
                data: {
                    participant_id: participant.id
                },
                success: function(response) {
                    if (response.success) {
                        successCount++;

                        // Si l'envoi par email est activé
                        if (sendEmails && participant.email) {
                            sendAttestationEmail(response.data.id);
                        }
                    } else {
                        errorCount++;
                    }

                    processNext();
                },
                error: function(xhr) {
                    errorCount++;
                    processNext();
                }
            });
        }

        function updateProgress(current, total, success, error) {
            const percent = (current / total) * 100;
            $('#spinnerModal .modal-body').html(`
                <div class="text-center">
                    <div class="spinner-border text-light mb-3" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="text-light mb-2">Génération en cours...</p>
                    <div class="progress mb-2">
                        <div class="progress-bar bg-success" style="width: ${(success/total)*100}%"></div>
                        <div class="progress-bar bg-danger" style="width: ${(error/total)*100}%"></div>
                        <div class="progress-bar bg-secondary" style="width: ${((current - success - error)/total)*100}%"></div>
                    </div>
                    <small class="text-light">
                        ${current}/${total} participants traités -
                        ${success} succès - ${error} erreurs
                    </small>
                </div>
            `);
        }

        // Démarrer le traitement
        processNext();
    }

    function sendAttestationEmail(attestationId) {
        $.ajax({
            url: `/attestations/${attestationId}/send-email`,
            method: 'POST',
            error: function(xhr) {
                console.error('Erreur envoi email:', xhr);
            }
        });
    }

    // ============= AFFICHAGE DÉTAILS =============

    $(document).on('click', '.view-btn', function() {
        const attestationId = $(this).data('id');
        showSpinner();

        $.ajax({
            url: `/attestations/${attestationId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const attestation = response.data;

                    $('#view_attestation_number').text(attestation.attestation_number);

                    if (attestation.participant) {
                        const fullName = attestation.participant.name;
                        $('#view_participant_name').text(fullName);
                        $('#view_participant_email').text(attestation.participant.email || 'Non renseigné');
                    }

                    $('#view_periode').text(attestation.periode ? attestation.periode.libelle : 'N/A');
                    $('#view_status').html(attestation.status === 'sent'
                        ? '<span class="badge bg-success">Envoyée</span>'
                        : '<span class="badge bg-warning">En attente</span>');
                    $('#view_issue_date').text(new Date(attestation.issue_date).toLocaleDateString('fr-FR'));
                    $('#view_sent_at').text(attestation.sent_at ? new Date(attestation.sent_at).toLocaleDateString('fr-FR') : '-');
                    $('#view_generated_by').text(attestation.generated_by ? attestation.generated_by.name : 'Système');
                    $('#view_view_count').text(attestation.view_count || 0);
                    $('#view_last_viewed_at').text(attestation.last_viewed_at ? new Date(attestation.last_viewed_at).toLocaleDateString('fr-FR') : '-');

                    $('#attestationViewModal').modal('show');
                }
                hideSpinner();
            },
            error: function(xhr) {
                hideSpinner();
                showToast('Erreur lors du chargement des détails', 'error');
            }
        });
    });

    // ============= ENVOI PAR EMAIL =============

    $(document).on('click', '.send-email-btn', function() {
        const attestationId = $(this).data('id');

        Swal.fire({
            title: 'Envoyer par email',
            text: 'Voulez-vous envoyer cette attestation par email au participant ?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Oui, envoyer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                showSpinner();

                $.ajax({
                    url: `/attestations/${attestationId}/send-email`,
                    method: 'POST',
                    success: function(response) {
                        hideSpinner();
                        if (response.success) {
                            showToast(response.message, 'success');
                            dataTable.ajax.reload();
                            loadStats();
                        } else {
                            showToast(response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        hideSpinner();
                        showToast(xhr.responseJSON?.message || 'Erreur lors de l\'envoi', 'error');
                    }
                });
            }
        });
    });

    // ============= SUPPRESSION =============

    $(document).on('click', '.delete-btn', function() {
        const attestationId = $(this).data('id');
        $('#delete_attestation_id').val(attestationId);
        $('#attestationDeleteModal').modal('show');
    });

    $('#confirmDeleteBtn').on('click', function() {
        showSpinner();
        const attestationId = $('#delete_attestation_id').val();

        $.ajax({
            url: `/attestations/${attestationId}`,
            method: 'DELETE',
            success: function(response) {
                if (response.success) {
                    $('#attestationDeleteModal').modal('hide');
                    dataTable.ajax.reload();
                    loadStats();
                    showToast(response.message, 'success');
                }
                hideSpinner();
            },
            error: function(xhr) {
                hideSpinner();
                showToast(xhr.responseJSON?.message || 'Erreur lors de la suppression', 'error');
            }
        });
    });

    // ============= RÉINITIALISER LES FORMULAIRES =============

    $('#singleGenerateModal').on('hidden.bs.modal', function() {
        $('#singleGenerateForm')[0].reset();

        // Réinitialiser Select2
        $('#single_participant_id').empty().prop('disabled', true);
        $('#single_participant_id').select2('destroy');
        $('#single_participant_id').html('<option value="">Choisissez d\'abord une période</option>');

        $('#participantInfo').addClass('d-none');
        $('#existingAttestationAlert').addClass('d-none');
        $('#single_send_email').prop('disabled', false);
        $('#single_send_email').next('.form-check-label').find('small').remove();
        clearErrors();
    });

    $('#bulkGenerateModal').on('hidden.bs.modal', function() {
        $('#bulkGenerateForm')[0].reset();
        $('#participantsInfo').addClass('d-none').removeClass('alert-danger').addClass('alert-warning');
        clearErrors();
    });

    // ============= GÉNÉRATION INDIVIDUELLE AVEC SELECT2 =============

// Initialiser Select2
function initializeParticipantSelect() {
    $('.select2-participant').select2({
        theme: 'bootstrap-5',
        language: 'fr',
        placeholder: 'Rechercher un participant...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#singleGenerateModal'),
        templateResult: formatParticipantResult,
        templateSelection: formatParticipantSelection,
        escapeMarkup: function(markup) {
            return markup;
        }
    });
}

// Formater l'affichage dans la dropdown
function formatParticipantResult(participant) {
    if (!participant.id) {
        return participant.text;
    }

    if (participant.element && participant.element.dataset) {
        const email = participant.element.dataset.email || '';
        const organisation = participant.element.dataset.organisation || '';
        const hasAttestation = participant.element.dataset.hasAttestation === 'true';

        let badge = hasAttestation
            ? '<span class="badge bg-warning float-end">Déjà une attestation</span>'
            : '<span class="badge bg-success float-end">Sans attestation</span>';

        return $(
            `<div>
                <div class="fw-bold">${participant.text}</div>
                <div class="small text-muted">
                    ${email ? `${email} • ` : ''}${organisation}
                </div>
                ${badge}
            </div>`
        );
    }

    return participant.text;
}

// Formater l'affichage dans le select sélectionné
function formatParticipantSelection(participant) {
    if (!participant.id) {
        return participant.text;
    }

    if (participant.element && participant.element.dataset) {
        const hasAttestation = participant.element.dataset.hasAttestation === 'true';
        const badge = hasAttestation ? ' <span class="badge bg-warning">Déjà attestation</span>' : '';
        return participant.text + badge;
    }

    return participant.text;
}

// Charger les participants avec Select2 (AJAX)
$('#single_periode_id').on('change', function() {
    const periodeId = $(this).val();
    const participantSelect = $('#single_participant_id');

    if (!periodeId) {
        participantSelect.prop('disabled', true).empty().append('<option value="">Choisissez d\'abord une période</option>');
        participantSelect.trigger('change');
        $('#participantInfo').addClass('d-none');
        $('#existingAttestationAlert').addClass('d-none');
        return;
    }

    // Réinitialiser Select2
    participantSelect.empty().prop('disabled', false);
    participantSelect.select2({
        theme: 'bootstrap-5',
        language: 'fr',
        placeholder: 'Rechercher un participant...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#singleGenerateModal'),
        ajax: {
            url: `/participants/periode/${periodeId}/list`,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term, // terme de recherche
                    page: params.page || 1
                };
            },
            processResults: function(response, params) {
                if (response.success) {
                    const participants = response.data;

                    return {
                        results: participants.map(participant => ({
                            id: participant.id,
                            text: participant.name,
                            email: participant.email,
                            organisation: participant.organisation,
                            hasAttestation: participant.has_attestation
                        })),
                        pagination: {
                            more: false // Désactiver la pagination infinie pour l'instant
                        }
                    };
                }

                return { results: [] };
            },
            cache: true
        },
        templateResult: formatParticipantResult,
        templateSelection: formatParticipantSelection,
        escapeMarkup: function(markup) {
            return markup;
        }
    });

    // Réinitialiser les infos
    $('#participantInfo').addClass('d-none');
    $('#existingAttestationAlert').addClass('d-none');
    $('#single_send_email').prop('disabled', false);
    $('#single_send_email').next('.form-check-label').find('small').remove();
});

// Afficher les infos du participant sélectionné
$('#single_participant_id').on('change', function() {
    const selectedData = $(this).select2('data')[0];

    if (selectedData && selectedData.id) {
        // Afficher les informations du participant
        $('#participantName').text(selectedData.text);
        $('#participantEmail').text(selectedData.email || 'Aucun email');
        $('#participantOrganisation').text(selectedData.organisation || 'Non renseigné');
        $('#participantInfo').removeClass('d-none');

        // Avertissement si attestation existe déjà
        if (selectedData.hasAttestation) {
            $('#existingAttestationAlert').removeClass('d-none');
        } else {
            $('#existingAttestationAlert').addClass('d-none');
        }

        // Désactiver l'envoi par email si pas d'email
        if (!selectedData.email) {
            $('#single_send_email').prop('checked', false).prop('disabled', true);
            if (!$('#single_send_email').next('.form-check-label').find('small').length) {
                $('#single_send_email').next('.form-check-label').append(' <small class="text-danger">(Email manquant)</small>');
            }
        } else {
            $('#single_send_email').prop('disabled', false);
            $('#single_send_email').next('.form-check-label').find('small').remove();
        }
    } else {
        $('#participantInfo').addClass('d-none');
        $('#existingAttestationAlert').addClass('d-none');
        $('#single_send_email').prop('disabled', false);
        $('#single_send_email').next('.form-check-label').find('small').remove();
    }
});

// Génération individuelle
$('#confirmSingleGenerateBtn').on('click', function() {
    const periodeId = $('#single_periode_id').val();
    const participantId = $('#single_participant_id').val();
    const sendEmail = $('#single_send_email').is(':checked');

    if (!periodeId || !participantId) {
        showToast('Veuillez sélectionner une période et un participant', 'error');
        return;
    }

    showSpinner();

    $.ajax({
        url: '/attestations',
        method: 'POST',
        data: {
            participant_id: participantId
        },
        success: function(response) {
            hideSpinner();

            if (response.success) {
                $('#singleGenerateModal').modal('hide');

                let message = `Attestation créée avec succès pour ${response.data.participant.name}`;

                // Si envoi par email demandé
                if (sendEmail && response.data.participant.email) {
                    sendSingleAttestationEmail(response.data.id, response.data.participant.email);
                    message += ' et envoyée par email';
                } else if (sendEmail && !response.data.participant.email) {
                    message += ' (mais non envoyée par email - adresse manquante)';
                }

                showToast(message, 'success');
                dataTable.ajax.reload();
                loadStats();

            } else {
                showToast(response.message, 'error');
            }
        },
        error: function(xhr) {
            hideSpinner();

            if (xhr.status === 422) {
                // Erreur de validation (attestation existe déjà)
                showToast(xhr.responseJSON.message, 'error');
            } else {
                showToast(xhr.responseJSON?.message || 'Erreur lors de la génération', 'error');
            }
        }
    });
});

function sendSingleAttestationEmail(attestationId, participantEmail) {
    $.ajax({
        url: `/attestations/${attestationId}/send-email`,
        method: 'POST',
        success: function(response) {
            if (response.success) {
                console.log('Email envoyé avec succès à:', participantEmail);
            } else {
                console.error('Erreur envoi email:', response.message);
            }
        },
        error: function(xhr) {
            console.error('Erreur envoi email:', xhr.responseJSON?.message);
        }
    });
}

});
</script>
@endsection

@extends('layouts.front')

@section('title', 'Participants')

@section('content')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Gestion des Participants</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Participants</li>
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

                <!-- Modal Ajout Participant -->
                <div class="modal fade" id="participantModal" tabindex="-1" aria-labelledby="participantModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="participantModalLabel">
                                    <i class="bi bi-person-plus"></i> Nouveau Participant
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="participantForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Période <span class="text-danger">*</span></label>
                                            <select id="periode_id" name="periode_id" class="form-select">
                                                <option value="">Sélectionnez une période</option>
                                                @foreach($periodes as $periode)
                                                    <option value="{{ $periode->id }}">{{ $periode->libelle }}</option>
                                                @endforeach
                                            </select>
                                            <span id="error_periode_id" class="text-danger small"></span>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Noms et Prénoms <span class="text-danger">*</span></label>
                                            <input type="text" id="name" name="name" class="form-control"
                                                   placeholder="Ex: DOE John">
                                            <span id="error_name" class="text-danger small"></span>
                                        </div>
                                    </div>


                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" id="email" name="email" class="form-control"
                                                   placeholder="email@exemple.com">
                                            <span id="error_email" class="text-danger small"></span>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Téléphone</label>
                                            <input type="text" id="phone" name="phone" class="form-control"
                                                   placeholder="Numéro de téléphone">
                                            <span id="error_phone" class="text-danger small"></span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Organisation</label>
                                            <input type="text" id="organisation" name="organisation" class="form-control"
                                                   placeholder="Entreprise ou organisation">
                                            <span id="error_organisation" class="text-danger small"></span>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Fonction</label>
                                            <input type="text" id="fonction" name="fonction" class="form-control"
                                                   placeholder="Poste ou fonction">
                                            <span id="error_fonction" class="text-danger small"></span>
                                        </div>
                                    </div>

                                    <div class="form-check">
                                        <input type="checkbox" id="is_active" name="is_active" class="form-check-input" checked>
                                        <label class="form-check-label" for="is_active">Participant actif</label>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle"></i> Annuler
                                </button>
                                <button type="button" class="btn btn-primary" id="saveParticipantBtn">
                                    <i class="bi bi-save"></i> Enregistrer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Modification Participant -->
                <div class="modal fade" id="participantEditModal" tabindex="-1" aria-labelledby="participantEditModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-warning">
                                <h5 class="modal-title" id="participantEditModalLabel">
                                    <i class="bi bi-person-gear"></i> Modifier le Participant
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="participantEditForm">
                                    @csrf
                                    <input type="hidden" id="edit_participant_id">

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Période <span class="text-danger">*</span></label>
                                            <select id="edit_periode_id" name="periode_id" class="form-select">
                                                @foreach($periodes as $periode)
                                                    <option value="{{ $periode->id }}">{{ $periode->libelle }}</option>
                                                @endforeach
                                            </select>
                                            <span id="error_edit_periode_id" class="text-danger small"></span>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Noms et Prénoms <span class="text-danger">*</span></label>
                                            <input type="text" id="edit_name" name="name" class="form-control">
                                            <span id="error_edit_name" class="text-danger small"></span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" id="edit_email" name="email" class="form-control">
                                            <span id="error_edit_email" class="text-danger small"></span>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Téléphone</label>
                                            <input type="text" id="edit_phone" name="phone" class="form-control">
                                            <span id="error_edit_phone" class="text-danger small"></span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Organisation</label>
                                            <input type="text" id="edit_organisation" name="organisation" class="form-control">
                                            <span id="error_edit_organisation" class="text-danger small"></span>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Fonction</label>
                                            <input type="text" id="edit_fonction" name="fonction" class="form-control">
                                            <span id="error_edit_fonction" class="text-danger small"></span>
                                        </div>
                                    </div>

                                    <div class="form-check">
                                        <input type="checkbox" id="edit_is_active" name="is_active" class="form-check-input">
                                        <label class="form-check-label" for="edit_is_active">Participant actif</label>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle"></i> Annuler
                                </button>
                                <button type="button" class="btn btn-warning" id="updateParticipantBtn">
                                    <i class="bi bi-save"></i> Modifier
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Détails Participant -->
                <div class="modal fade" id="participantViewModal" tabindex="-1" aria-labelledby="participantViewModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-info text-white">
                                <h5 class="modal-title" id="participantViewModalLabel">
                                    <i class="bi bi-person-badge"></i> Détails du Participant
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <table class="table table-borderless">
                                            <tr>
                                                <th width="30%">Nom complet:</th>
                                                <td><span id="view_full_name" class="fw-bold"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Période:</th>
                                                <td><span id="view_periode"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Matricule:</th>
                                                <td><span id="view_matricule"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Email:</th>
                                                <td><span id="view_email"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Téléphone:</th>
                                                <td><span id="view_phone"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Organisation:</th>
                                                <td><span id="view_organisation"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Fonction:</th>
                                                <td><span id="view_fonction"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Statut:</th>
                                                <td><span id="view_status"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Attestations:</th>
                                                <td><span id="view_attestations_count" class="badge bg-success"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Créé le:</th>
                                                <td><span id="view_created_at"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Dernière modification:</th>
                                                <td><span id="view_updated_at"></span></td>
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
                <div class="modal fade" id="participantDeleteModal" tabindex="-1" aria-labelledby="participantDeleteModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="participantDeleteModalLabel">
                                    <i class="bi bi-trash"></i> Confirmer la Suppression
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="delete_participant_id">
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Attention!</strong> Cette action est irréversible.
                                </div>
                                <p class="mb-0">Voulez-vous vraiment supprimer ce participant ?</p>
                                <p class="text-muted small">Cette action ne sera possible que si le participant n'a pas d'attestations.</p>
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

                <!-- Modal Import -->
                <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title" id="importModalLabel">
                                    <i class="bi bi-upload"></i> Importer des Participants
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="importForm" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label">Période <span class="text-danger">*</span></label>
                                        <select id="import_periode_id" name="periode_id" class="form-select" required>
                                            <option value="">Sélectionnez une période</option>
                                            @foreach($periodes as $periode)
                                                <option value="{{ $periode->id }}">{{ $periode->libelle }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Fichier <span class="text-danger">*</span></label>
                                        <input type="file" id="import_file" name="file" class="form-control"
                                               accept=".csv,.xlsx,.xls" required>
                                        <div class="form-text">
                                            Formats acceptés: CSV, Excel (XLSX, XLS). Taille max: 2MB
                                        </div>
                                    </div>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        <strong>Information:</strong> Le fichier doit contenir les colonnes:
                                        Prénom, Nom, Email, Téléphone, Matricule, Organisation, Fonction
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="button" class="btn btn-success" id="confirmImportBtn">
                                    <i class="bi bi-upload"></i> Importer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des Participants -->
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="bi bi-people"></i> Liste des Participants
                            </h4>
                            <div>
                                @can('manage participants')
                                <button class="btn btn-light btn-sm me-2" id="downloadTemplateBtn">
                                    <i class="bi bi-download"></i> Template
                                </button>
                                <button class="btn btn-light btn-sm me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                                    <i class="bi bi-upload"></i> Importer
                                </button>
                                <button class="btn btn-light btn-sm me-2" id="exportBtn">
                                    <i class="bi bi-download"></i> Exporter
                                </button>
                                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#participantModal">
                                    <i class="bi bi-person-plus"></i> Nouveau
                                </button>
                                @endcan
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
                                        <option value="1">Actifs</option>
                                        <option value="0">Inactifs</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Attestation:</label>
                                    <select id="filter_attestation" class="form-select form-select-sm">
                                        <option value="">Tous</option>
                                        <option value="1">Avec attestation</option>
                                        <option value="0">Sans attestation</option>
                                    </select>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-bordered" id="participantsTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="5%">N°</th>
                                            <th width="20%">Participant</th>
                                            <th width="15%">Période</th>
                                            <th width="15%">Contact</th>
                                            <th width="15%">Organisation</th>
                                            <th width="10%" class="text-center">Attestations</th>
                                            <th width="10%" class="text-center">Statut</th>
                                            <th width="15%" class="text-center">Actions</th>
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
.badges-container .badge {
    margin: 2px;
}
</style>
@endsection

@section('scripts')

<script>
/**
 * Gestion des Participants - CRUD avec DataTables
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

    // ============= INITIALISATION DATATABLE =============

function initializeDataTable() {
    dataTable = $('#participantsTable').DataTable({
        processing: true,
        serverSide: false,
        responsive: true,
        ajax: {
            url: '/participants',
            type: 'GET',
            dataSrc: function (json) {
                console.log('Réponse API:', json); // Debug
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
                $('#participantsTable tbody').html(
                    `<tr><td colspan="8" class="text-center text-danger">${errorMessage}</td></tr>`
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
                data: null,
                render: function(data) {
                    // Gestion des participants sans prénom
                    const displayName =  data.name ? data.name : '<span class="text-muted">Non renseigné</span>';

                    let info = `<strong>${displayName}</strong>`;
                    if (data.matricule) {
                        info += `<br><small class="text-muted">${data.matricule}</small>`;
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
                data: null,
                render: function(data) {
                    let contact = '';
                    if (data.email) {
                        contact += `<div><i class="bi bi-envelope"></i> ${data.email}</div>`;
                    }
                    if (data.phone) {
                        contact += `<div><i class="bi bi-phone"></i> ${data.phone}</div>`;
                    }
                    return contact || '<span class="text-muted">Aucun contact</span>';
                }
            },
            {
                data: null,
                render: function(data) {
                    let orgInfo = '';
                    if (data.organisation) {
                        orgInfo += `<strong>${data.organisation}</strong>`;
                    }
                    if (data.fonction) {
                        orgInfo += orgInfo ? `<br><small>${data.fonction}</small>` : data.fonction;
                    }
                    return orgInfo || '<span class="text-muted">Non renseigné</span>';
                }
            },
            {
                data: null, // ← Changer de 'attestations_count' à null
                render: function(data, type, row) {
                    // Vérifier si les attestations sont chargées
                    if (data.attestations && Array.isArray(data.attestations)) {
                        return `<span class="badge bg-success">${data.attestations.length}</span>`;
                    }
                    // Sinon, utiliser attestations_count si disponible
                    else if (data.attestations_count !== undefined) {
                        return `<span class="badge bg-success">${data.attestations_count}</span>`;
                    }
                    // Ou vérifier avec la méthode hasAttestation si disponible
                    else if (data.has_attestation !== undefined) {
                        return data.has_attestation
                            ? '<span class="badge bg-success">Oui</span>'
                            : '<span class="badge bg-secondary">Non</span>';
                    }
                    // Fallback
                    return '<span class="badge bg-secondary">0</span>';
                },
                className: 'text-center'
            },
            {
                data: 'is_active',
                render: function(data) {
                    return data
                        ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Actif</span>'
                        : '<span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Inactif</span>';
                },
                className: 'text-center'
            },
            {
                data: 'id',
                render: function(data, type, row) {
                    // Vérifier si le participant a des attestations
                    const hasAttestations = (row.attestations && row.attestations.length > 0) ||
                                          (row.attestations_count > 0) ||
                                          (row.has_attestation === true);

                    const deleteTitle = hasAttestations
                        ? 'Impossible de supprimer (attestations existantes)'
                        : 'Supprimer';

                    const deleteBtn = hasAttestations
                        ? '<button class="btn btn-danger btn-sm" disabled title="' + deleteTitle + '"><i class="bi bi-trash"></i></button>'
                        : `<button class="btn btn-danger btn-sm delete-btn" data-id="${data}" title="${deleteTitle}"><i class="bi bi-trash"></i></button>`;

                    return `
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-info view-btn" data-id="${data}" title="Détails">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-warning edit-btn" data-id="${data}" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </button>
                            ${deleteBtn}
                            <button class="btn btn-success toggle-status-btn" data-id="${data}"
                                    data-status="${row.is_active}" title="Changer statut">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                        </div>
                    `;
                },
                className: 'text-center',
                orderable: false
            }
        ],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[1, 'asc']]
    });

    // Appliquer les filtres
    $('#filter_periode, #filter_status, #filter_attestation').on('change', function() {
        applyFilters();
    });
}

    // Initialiser DataTable
    initializeDataTable();

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

    function displayErrors(errors) {
        clearErrors();
        $.each(errors, function(field, messages) {
            $(`#error_${field}`).text(messages[0]);
            $(`#${field}`).addClass('is-invalid');
        });
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
        const attestation = $('#filter_attestation').val();

        let url = '/participants?';
        const params = [];

        if (periode) params.push(`periode_id=${periode}`);
        if (status) params.push(`is_active=${status}`);
        if (attestation) params.push(`has_attestation=${attestation}`);

        if (params.length > 0) {
            url += params.join('&');
        }

        dataTable.ajax.url(url).load();
    }

    // ============= AJOUT DE PARTICIPANT =============

    $('#saveParticipantBtn').on('click', function() {
        clearErrors();
        showSpinner();

        const formData = new FormData();
        formData.append('periode_id', $('#periode_id').val());
        formData.append('name', $('#name').val());
        formData.append('email', $('#email').val());
        formData.append('phone', $('#phone').val());
        formData.append('matricule', $('#matricule').val());
        formData.append('organisation', $('#organisation').val());
        formData.append('fonction', $('#fonction').val());
        formData.append('is_active', $('#is_active').is(':checked') ? 1 : 0);

        $.ajax({
            url: '/participants',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#participantModal').modal('hide');
                    $('#participantForm')[0].reset();
                    dataTable.ajax.reload();
                    showToast(response.message, 'success');
                }
                hideSpinner();
            },
            error: function(xhr) {
                hideSpinner();
                if (xhr.status === 422) {
                    displayErrors(xhr.responseJSON.errors);
                } else {
                    showToast(xhr.responseJSON?.message || 'Erreur lors de la création', 'error');
                }
            }
        });
    });

    // ============= AFFICHAGE DÉTAILS =============

    $(document).on('click', '.view-btn', function() {
        const participantId = $(this).data('id');
        showSpinner();

        $.ajax({
            url: `/participants/${participantId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const participant = response.data;

                    // Gestion du nom complet (avec ou sans prénom)
                    const fullName = participant.name;

                    $('#view_full_name').text(fullName);
                    $('#view_periode').text(participant.periode ? participant.periode.libelle : 'N/A');
                    $('#view_matricule').text(participant.matricule || 'Non renseigné');
                    $('#view_email').text(participant.email || 'Non renseigné');
                    $('#view_phone').text(participant.phone || 'Non renseigné');
                    $('#view_organisation').text(participant.organisation || 'Non renseigné');
                    $('#view_fonction').text(participant.fonction || 'Non renseigné');
                    $('#view_status').html(participant.is_active
                        ? '<span class="badge bg-success">Actif</span>'
                        : '<span class="badge bg-secondary">Inactif</span>');
                    $('#view_attestations_count').text(participant.attestations_count || 0);
                    $('#view_created_at').text(new Date(participant.created_at).toLocaleDateString('fr-FR'));
                    $('#view_updated_at').text(new Date(participant.updated_at).toLocaleDateString('fr-FR'));

                    $('#participantViewModal').modal('show');
                }
                hideSpinner();
            },
            error: function(xhr) {
                hideSpinner();
                showToast('Erreur lors du chargement des détails', 'error');
            }
        });
    });

    // ============= MODIFICATION =============

    $(document).on('click', '.edit-btn', function() {
        const participantId = $(this).data('id');
        showSpinner();

        $.ajax({
            url: `/participants/${participantId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const participant = response.data;

                    $('#edit_participant_id').val(participant.id);
                    $('#edit_periode_id').val(participant.periode_id);
                    $('#edit_name').val(participant.name);
                    $('#edit_email').val(participant.email || '');
                    $('#edit_phone').val(participant.phone || '');
                    $('#edit_matricule').val(participant.matricule || '');
                    $('#edit_organisation').val(participant.organisation || '');
                    $('#edit_fonction').val(participant.fonction || '');
                    $('#edit_is_active').prop('checked', participant.is_active);

                    $('#participantEditModal').modal('show');
                }
                hideSpinner();
            },
            error: function(xhr) {
                hideSpinner();
                showToast('Erreur lors du chargement des données', 'error');
            }
        });
    });

    $('#updateParticipantBtn').on('click', function() {
        clearErrors();
        showSpinner();

        const participantId = $('#edit_participant_id').val();
        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('periode_id', $('#edit_periode_id').val());
        formData.append('first_name', $('#edit_first_name').val());
        formData.append('name', $('#edit_name').val());
        formData.append('email', $('#edit_email').val());
        formData.append('phone', $('#edit_phone').val());
        formData.append('matricule', $('#edit_matricule').val());
        formData.append('organisation', $('#edit_organisation').val());
        formData.append('fonction', $('#edit_fonction').val());
        formData.append('is_active', $('#edit_is_active').is(':checked') ? 1 : 0);

        $.ajax({
            url: `/participants/${participantId}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#participantEditModal').modal('hide');
                    dataTable.ajax.reload();
                    showToast(response.message, 'success');
                }
                hideSpinner();
            },
            error: function(xhr) {
                hideSpinner();
                if (xhr.status === 422) {
                    displayErrors(xhr.responseJSON.errors);
                } else {
                    showToast(xhr.responseJSON?.message || 'Erreur lors de la modification', 'error');
                }
            }
        });
    });

    // ============= SUPPRESSION =============

    $(document).on('click', '.delete-btn', function() {
        const participantId = $(this).data('id');
        $('#delete_participant_id').val(participantId);
        $('#participantDeleteModal').modal('show');
    });

    $('#confirmDeleteBtn').on('click', function() {
        showSpinner();
        const participantId = $('#delete_participant_id').val();

        $.ajax({
            url: `/participants/${participantId}`,
            method: 'DELETE',
            success: function(response) {
                if (response.success) {
                    $('#participantDeleteModal').modal('hide');
                    dataTable.ajax.reload();
                    showToast(response.message, 'success');
                } else {
                    showToast(response.message, 'error');
                $('#participantDeleteModal').modal('hide');
                }
                hideSpinner();
            },
            error: function(xhr) {
                hideSpinner();
                showToast(xhr.responseJSON?.message || 'Erreur lors de la suppression', 'error');
            }
        });
    });

    // ============= CHANGER LE STATUT =============

    $(document).on('click', '.toggle-status-btn', function() {
        const participantId = $(this).data('id');
        showSpinner();

        const formData = new FormData();
        formData.append('_method', 'PATCH');

        $.ajax({
            url: `/participants/${participantId}/toggle-status`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    dataTable.ajax.reload();
                    showToast(response.message, 'success');
                }
                hideSpinner();
            },
            error: function(xhr) {
                hideSpinner();
                showToast(xhr.responseJSON?.message || 'Erreur lors du changement de statut', 'error');
            }
        });
    });

    // ============= TÉLÉCHARGER LE TEMPLATE =============

    $('#downloadTemplateBtn').on('click', function() {
        window.location.href = '/participants/download-template';
    });

    // ============= IMPORT/EXPORT =============

    $('#confirmImportBtn').on('click', function() {
        const formData = new FormData($('#importForm')[0]);
        showSpinner();

        $.ajax({
            url: '/participants/import',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#importModal').modal('hide');
                    $('#importForm')[0].reset();
                    dataTable.ajax.reload();

                    // Afficher un résumé détaillé
                    if (response.errors && response.errors.length > 0) {
                        let errorMessage = `Import partiel: ${response.data.imported} importés, ${response.data.failed} échecs.`;

                        // Afficher les erreurs détaillées
                        let detailedErrors = 'Erreurs détaillées:\n';
                        response.errors.forEach(error => {
                            detailedErrors += `Ligne ${error.line}: ${error.errors.join(', ')}\n`;
                        });

                        Swal.fire({
                            icon: 'warning',
                            title: 'Import avec avertissements',
                            html: `
                                <p>${errorMessage}</p>
                                <details style="text-align: left; margin-top: 10px;">
                                    <summary>Voir les détails des erreurs</summary>
                                    <pre style="background: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto;">${detailedErrors}</pre>
                                </details>
                            `,
                            confirmButtonText: 'OK'
                        });
                    } else {
                        showToast(response.message, 'success');
                    }
                }
                hideSpinner();
            },
            error: function(xhr) {
                hideSpinner();
                if (xhr.status === 422) {
                    // Erreur de validation
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = 'Erreurs dans le formulaire:\n';
                    for (const field in errors) {
                        errorMessage += `- ${errors[field].join(', ')}\n`;
                    }
                    Swal.fire('Erreur', errorMessage, 'error');
                } else {
                    showToast(xhr.responseJSON?.message || 'Erreur lors de l\'import', 'error');
                }
            }
        });
    });

    $('#exportBtn').on('click', function() {
        const periode = $('#filter_periode').val();
        let url = '/participants/export';

        if (periode) {
            url += `?periode_id=${periode}`;
        }

        // Afficher une confirmation
        Swal.fire({
            title: 'Exporter les participants',
            text: 'Voulez-vous exporter la liste des participants ?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Exporter',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                showSpinner();
                window.location.href = url;
                setTimeout(() => hideSpinner(), 2000);
            }
        });
    });

    // ============= RÉINITIALISER LES FORMULAIRES =============

    $('#participantModal').on('hidden.bs.modal', function() {
        $('#participantForm')[0].reset();
        clearErrors();
    });

    $('#participantEditModal').on('hidden.bs.modal', function() {
        $('#participantEditForm')[0].reset();
        clearErrors();
    });

    $('#importModal').on('hidden.bs.modal', function() {
        $('#importForm')[0].reset();
    });
});
</script>
@endsection

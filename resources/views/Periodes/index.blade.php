@extends('layouts.front')

@section('title', 'Périodes')

@section('content')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Gestion des Périodes</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Périodes</li>
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

                <!-- Modal Ajout Période -->
                <div class="modal fade" id="periodeModal" tabindex="-1" aria-labelledby="periodeModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="periodeModalLabel">
                                    <i class="bi bi-calendar-plus"></i> Ajouter une Période
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="periodeForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Libellé de la Période <span class="text-danger">*</span></label>
                                            <input type="text" id="libelle" name="libelle" class="form-control"
                                                   placeholder="Ex: Décembre 2025 à Mars 2026">
                                            <span id="error_libelle" class="text-danger small"></span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Mois de Début <span class="text-danger">*</span></label>
                                            <select id="mois_debut" name="mois_debut" class="form-select">
                                                <option value="">Sélectionnez...</option>
                                                @foreach(range(1, 12) as $mois)
                                                    <option value="{{ $mois }}">
                                                        {{ DateTime::createFromFormat('!m', $mois)->format('F') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span id="error_mois_debut" class="text-danger small"></span>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Année de Début <span class="text-danger">*</span></label>
                                            <select id="annee_debut" name="annee_debut" class="form-select">
                                                <option value="">Sélectionnez...</option>
                                                @foreach(range(date('Y') - 2, date('Y') + 5) as $annee)
                                                    <option value="{{ $annee }}">{{ $annee }}</option>
                                                @endforeach
                                            </select>
                                            <span id="error_annee_debut" class="text-danger small"></span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Mois de Fin <span class="text-danger">*</span></label>
                                            <select id="mois_fin" name="mois_fin" class="form-select">
                                                <option value="">Sélectionnez...</option>
                                                @foreach(range(1, 12) as $mois)
                                                    <option value="{{ $mois }}">
                                                        {{ DateTime::createFromFormat('!m', $mois)->format('F') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span id="error_mois_fin" class="text-danger small"></span>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Année de Fin <span class="text-danger">*</span></label>
                                            <select id="annee_fin" name="annee_fin" class="form-select">
                                                <option value="">Sélectionnez...</option>
                                                @foreach(range(date('Y') - 2, date('Y') + 5) as $annee)
                                                    <option value="{{ $annee }}">{{ $annee }}</option>
                                                @endforeach
                                            </select>
                                            <span id="error_annee_fin" class="text-danger small"></span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Description (Optionnelle)</label>
                                        <textarea id="description" name="description" class="form-control" rows="3"
                                                  placeholder="Description de la période..."></textarea>
                                        <span id="error_description" class="text-danger small"></span>
                                    </div>

                                    <div class="form-check">
                                        <input type="checkbox" id="is_active" name="is_active" class="form-check-input" checked>
                                        <label class="form-check-label" for="is_active">Période Active</label>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle"></i> Annuler
                                </button>
                                <button type="button" class="btn btn-primary" id="savePeriodeBtn">
                                    <i class="bi bi-save"></i> Enregistrer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Modification Période -->
                <div class="modal fade" id="periodeEditModal" tabindex="-1" aria-labelledby="periodeEditModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-warning">
                                <h5 class="modal-title" id="periodeEditModalLabel">
                                    <i class="bi bi-pencil-square"></i> Modifier la Période
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="periodeEditForm">
                                    @csrf
                                    <input type="hidden" id="edit_periode_id">

                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Libellé de la Période <span class="text-danger">*</span></label>
                                            <input type="text" id="edit_libelle" name="libelle" class="form-control">
                                            <span id="error_edit_libelle" class="text-danger small"></span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Mois de Début <span class="text-danger">*</span></label>
                                            <select id="edit_mois_debut" name="mois_debut" class="form-select">
                                                @foreach(range(1, 12) as $mois)
                                                    <option value="{{ $mois }}">
                                                        {{ DateTime::createFromFormat('!m', $mois)->format('F') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span id="error_edit_mois_debut" class="text-danger small"></span>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Année de Début <span class="text-danger">*</span></label>
                                            <select id="edit_annee_debut" name="annee_debut" class="form-select">
                                                @foreach(range(date('Y') - 2, date('Y') + 5) as $annee)
                                                    <option value="{{ $annee }}">{{ $annee }}</option>
                                                @endforeach
                                            </select>
                                            <span id="error_edit_annee_debut" class="text-danger small"></span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Mois de Fin <span class="text-danger">*</span></label>
                                            <select id="edit_mois_fin" name="mois_fin" class="form-select">
                                                @foreach(range(1, 12) as $mois)
                                                    <option value="{{ $mois }}">
                                                        {{ DateTime::createFromFormat('!m', $mois)->format('F') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span id="error_edit_mois_fin" class="text-danger small"></span>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Année de Fin <span class="text-danger">*</span></label>
                                            <select id="edit_annee_fin" name="annee_fin" class="form-select">
                                                @foreach(range(date('Y') - 2, date('Y') + 5) as $annee)
                                                    <option value="{{ $annee }}">{{ $annee }}</option>
                                                @endforeach
                                            </select>
                                            <span id="error_edit_annee_fin" class="text-danger small"></span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
                                        <span id="error_edit_description" class="text-danger small"></span>
                                    </div>

                                    <div class="form-check">
                                        <input type="checkbox" id="edit_is_active" name="is_active" class="form-check-input">
                                        <label class="form-check-label" for="edit_is_active">Période Active</label>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle"></i> Annuler
                                </button>
                                <button type="button" class="btn btn-warning" id="updatePeriodeBtn">
                                    <i class="bi bi-save"></i> Modifier
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Détails Période -->
                <div class="modal fade" id="periodeViewModal" tabindex="-1" aria-labelledby="periodeViewModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-info text-white">
                                <h5 class="modal-title" id="periodeViewModalLabel">
                                    <i class="bi bi-info-circle"></i> Détails de la Période
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Libellé:</th>
                                        <td><span id="view_libelle" class="fw-bold"></span></td>
                                    </tr>
                                    <tr>
                                        <th>Période:</th>
                                        <td><span id="view_periode_dates"></span></td>
                                    </tr>
                                    <tr>
                                        <th>Description:</th>
                                        <td><span id="view_description"></span></td>
                                    </tr>
                                    <tr>
                                        <th>Statut:</th>
                                        <td><span id="view_status"></span></td>
                                    </tr>
                                    <tr>
                                        <th>Participants:</th>
                                        <td><span id="view_participants_count" class="badge bg-primary"></span></td>
                                    </tr>
                                    <tr>
                                        <th>Attestations:</th>
                                        <td><span id="view_attestations_count" class="badge bg-success"></span></td>
                                    </tr>
                                    <tr>
                                        <th>Créée le:</th>
                                        <td><span id="view_created_at"></span></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Suppression -->
                <div class="modal fade" id="periodeDeleteModal" tabindex="-1" aria-labelledby="periodeDeleteModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="periodeDeleteModalLabel">
                                    <i class="bi bi-trash"></i> Confirmer la Suppression
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="delete_periode_id">
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Attention!</strong> Cette action est irréversible.
                                </div>
                                <p class="mb-0">Voulez-vous vraiment supprimer cette période ?</p>
                                <p class="text-muted small">Toutes les données associées seront également supprimées.</p>
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

                <!-- Tableau des Périodes -->
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">
                                <i class="bi bi-list-ul"></i> Liste des Périodes
                                @can('create periodes')
                                <button class="btn btn-light btn-sm float-end" data-bs-toggle="modal" data-bs-target="#periodeModal">
                                    <i class="bi bi-plus-circle"></i> Nouvelle Période
                                </button>
                                @endcan
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-bordered" id="periodesTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="5%">N°</th>
                                            <th width="30%">Libellé</th>
                                            <th width="15%">Début</th>
                                            <th width="15%">Fin</th>
                                            <th width="10%" class="text-center">Participants</th>
                                            <th width="10%" class="text-center">Statut</th>
                                            <th width="15%" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="periodesData">
                                        <tr>
                                            <td colspan="7" class="text-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Chargement...</span>
                                                </div>
                                            </td>
                                        </tr>
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


@section('scripts')

<script>
/**
 * Gestion des Périodes - CRUD avec DataTables
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
    dataTable = $('#periodesTable').DataTable({
        processing: true,
        serverSide: false,
        responsive: true,
        ajax: {
            url: '/periodes',
            type: 'GET',
            dataSrc: function (json) {
                if (json.success && json.data) {
                    return json.data.data || json.data;
                }
                return [];
            },
            error: function(xhr, error, thrown) {
                console.error('Erreur AJAX:', xhr);
                // Afficher un message d'erreur dans le tableau
                let errorMessage = 'Erreur lors du chargement des données';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                $('#periodesTable tbody').html(
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
                data: 'libelle',
                render: function(data, type, row) {
                    let description = row.description ? `<br><small class="text-muted">${row.description}</small>` : '';
                    return `<strong>${data}</strong>${description}`;
                }
            },
            {
                data: null,
                render: function(data) {
                    return `<small>${data.mois_debut}/${data.annee_debut}</small>`;
                },
                className: 'text-center'
            },
            {
                data: null,
                render: function(data) {
                    return `<small>${data.mois_fin}/${data.annee_fin}</small>`;
                },
                className: 'text-center'
            },
            {
                data: 'participants_count',
                render: function(data) {
                    return `<span class="badge bg-primary">${data || 0}</span>`;
                },
                className: 'text-center'
            },
            {
                data: 'is_active',
                render: function(data) {
                    return data
                        ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>'
                        : '<span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Inactive</span>';
                },
                className: 'text-center'
            },
            {
                data: 'id',
                render: function(data, type, row) {
                    return `
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-info view-btn" data-id="${data}" title="Détails">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-warning edit-btn" data-id="${data}" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-danger delete-btn" data-id="${data}" title="Supprimer">
                                <i class="bi bi-trash"></i>
                            </button>
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
        // language: {
        //     url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
        // },
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[1, 'asc']]
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

    // ============= AJOUT DE PÉRIODE =============

    $('#savePeriodeBtn').on('click', function() {
        clearErrors();
        showSpinner();

        const formData = new FormData();
        formData.append('libelle', $('#libelle').val());
        formData.append('mois_debut', parseInt($('#mois_debut').val()));
        formData.append('annee_debut', parseInt($('#annee_debut').val()));
        formData.append('mois_fin', parseInt($('#mois_fin').val()));
        formData.append('annee_fin', parseInt($('#annee_fin').val()));
        formData.append('description', $('#description').val());
        formData.append('is_active', $('#is_active').is(':checked') ? 1 : 0);

        $.ajax({
            url: '/periodes',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#periodeModal').modal('hide');
                    $('#periodeForm')[0].reset();
                    dataTable.ajax.reload(); // Recharger DataTable
                    showToast(response.message, 'success');
                }
                hideSpinner();
            },
            error: function(xhr) {
                hideSpinner();
                if (xhr.status === 422) {
                    displayErrors(xhr.responseJSON.errors);
                } else {
                    showToast(xhr.responseJSON?.message || 'Erreur lors de l\'enregistrement', 'error');
                }
            }
        });
    });

    // ============= AFFICHAGE DÉTAILS =============

    $(document).on('click', '.view-btn', function() {
        const periodeId = $(this).data('id');
        showSpinner();

        $.ajax({
            url: `/periodes/${periodeId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const periode = response.data;

                    $('#view_libelle').text(periode.libelle);
                    $('#view_periode_dates').text(`${periode.mois_debut}/${periode.annee_debut} - ${periode.mois_fin}/${periode.annee_fin}`);
                    $('#view_description').text(periode.description || 'Aucune description');
                    $('#view_status').html(periode.is_active
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-secondary">Inactive</span>');
                    $('#view_participants_count').text(periode.participants_count || 0);
                    $('#view_attestations_count').text(periode.attestations_count || 0);
                    $('#view_created_at').text(new Date(periode.created_at).toLocaleDateString('fr-FR'));

                    $('#periodeViewModal').modal('show');
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
        const periodeId = $(this).data('id');
        showSpinner();

        $.ajax({
            url: `/periodes/${periodeId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const periode = response.data;

                    $('#edit_periode_id').val(periode.id);
                    $('#edit_libelle').val(periode.libelle);
                    $('#edit_mois_debut').val(periode.mois_debut);
                    $('#edit_annee_debut').val(periode.annee_debut);
                    $('#edit_mois_fin').val(periode.mois_fin);
                    $('#edit_annee_fin').val(periode.annee_fin);
                    $('#edit_description').val(periode.description);
                    $('#edit_is_active').prop('checked', periode.is_active);

                    $('#periodeEditModal').modal('show');
                }
                hideSpinner();
            },
            error: function(xhr) {
                hideSpinner();
                showToast('Erreur lors du chargement des données', 'error');
            }
        });
    });

    $('#updatePeriodeBtn').on('click', function() {
        clearErrors();
        showSpinner();

        const periodeId = $('#edit_periode_id').val();

        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('libelle', $('#edit_libelle').val());
        formData.append('mois_debut', parseInt($('#edit_mois_debut').val()));
        formData.append('annee_debut', parseInt($('#edit_annee_debut').val()));
        formData.append('mois_fin', parseInt($('#edit_mois_fin').val()));
        formData.append('annee_fin', parseInt($('#edit_annee_fin').val()));
        formData.append('description', $('#edit_description').val());
        formData.append('is_active', $('#edit_is_active').is(':checked') ? 1 : 0);

        $.ajax({
            url: `/periodes/${periodeId}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#periodeEditModal').modal('hide');
                    dataTable.ajax.reload(); // Recharger DataTable
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
        const periodeId = $(this).data('id');
        $('#delete_periode_id').val(periodeId);
        $('#periodeDeleteModal').modal('show');
    });

    $('#confirmDeleteBtn').on('click', function() {
        showSpinner();
        const periodeId = $('#delete_periode_id').val();

        $.ajax({
            url: `/periodes/${periodeId}`,
            method: 'DELETE',
            success: function(response) {
                if (response.success) {
                    $('#periodeDeleteModal').modal('hide');
                    dataTable.ajax.reload(); // Recharger DataTable
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

    // ============= CHANGER LE STATUT =============

    $(document).on('click', '.toggle-status-btn', function() {
        const periodeId = $(this).data('id');
        showSpinner();

        const formData = new FormData();
        formData.append('_method', 'PATCH');

        $.ajax({
            url: `/periodes/${periodeId}/toggle-status`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    dataTable.ajax.reload(); // Recharger DataTable
                    showToast(response.message, 'success');
                }
                hideSpinner();
            },
            error: function(xhr) {
                hideSpinner();
                showToast('Erreur lors du changement de statut', 'error');
            }
        });
    });

    // ============= RÉINITIALISER LES FORMULAIRES =============

    $('#periodeModal').on('hidden.bs.modal', function() {
        $('#periodeForm')[0].reset();
        clearErrors();
    });

    $('#periodeEditModal').on('hidden.bs.modal', function() {
        $('#periodeEditForm')[0].reset();
        clearErrors();
    });
});
</script>
@endsection

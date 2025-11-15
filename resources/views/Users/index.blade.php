@extends('layouts.front')

@section('title', 'Utilisateurs')

@section('content')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Gestion des Utilisateurs</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Utilisateurs</li>
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

                <!-- Modal Ajout Utilisateur -->
                <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="userModalLabel">
                                    <i class="bi bi-person-plus"></i> Nouvel Utilisateur
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="userForm" enctype="multipart/form-data">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nom complet <span class="text-danger">*</span></label>
                                            <input type="text" id="name" name="name" class="form-control"
                                                   placeholder="Nom et prénom">
                                            <span id="error_name" class="text-danger small"></span>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Login <span class="text-danger">*</span></label>
                                            <input type="text" id="login" name="login" class="form-control"
                                                   placeholder="Nom d'utilisateur">
                                            <span id="error_login" class="text-danger small"></span>
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
                                            <label class="form-label">Photo</label>
                                            <input type="file" id="photo" name="photo" class="form-control"
                                                   accept="image/jpeg,image/png,image/jpg">
                                            <span id="error_photo" class="text-danger small"></span>
                                            <div class="form-text">Formats acceptés: JPEG, PNG, JPG (max 2MB)</div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                            <input type="password" id="password" name="password" class="form-control"
                                                   placeholder="Minimum 6 caractères">
                                            <span id="error_password" class="text-danger small"></span>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Confirmation mot de passe <span class="text-danger">*</span></label>
                                            <input type="password" id="password_confirmation" name="password_confirmation"
                                                   class="form-control" placeholder="Répétez le mot de passe">
                                            <span id="error_password_confirmation" class="text-danger small"></span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Rôles <span class="text-danger">*</span></label>
                                            <select id="roles" name="roles[]" class="form-select" multiple>
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                                @endforeach
                                            </select>
                                            <span id="error_roles" class="text-danger small"></span>
                                            <div class="form-text">Maintenez Ctrl pour sélectionner plusieurs rôles</div>
                                        </div>
                                    </div>

                                    <div class="form-check">
                                        <input type="checkbox" id="is_active" name="is_active" class="form-check-input" checked>
                                        <label class="form-check-label" for="is_active">Compte actif</label>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle"></i> Annuler
                                </button>
                                <button type="button" class="btn btn-primary" id="saveUserBtn">
                                    <i class="bi bi-save"></i> Enregistrer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Modification Utilisateur -->
                <div class="modal fade" id="userEditModal" tabindex="-1" aria-labelledby="userEditModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-warning">
                                <h5 class="modal-title" id="userEditModalLabel">
                                    <i class="bi bi-person-gear"></i> Modifier l'Utilisateur
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="userEditForm" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" id="edit_user_id">

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nom complet <span class="text-danger">*</span></label>
                                            <input type="text" id="edit_name" name="name" class="form-control">
                                            <span id="error_edit_name" class="text-danger small"></span>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Login <span class="text-danger">*</span></label>
                                            <input type="text" id="edit_login" name="login" class="form-control">
                                            <span id="error_edit_login" class="text-danger small"></span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" id="edit_email" name="email" class="form-control">
                                            <span id="error_edit_email" class="text-danger small"></span>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Photo</label>
                                            <input type="file" id="edit_photo" name="photo" class="form-control"
                                                   accept="image/jpeg,image/png,image/jpg">
                                            <span id="error_edit_photo" class="text-danger small"></span>
                                            <div class="form-text">Laisser vide pour conserver la photo actuelle</div>
                                            <div id="current_photo" class="mt-2"></div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nouveau mot de passe</label>
                                            <input type="password" id="edit_password" name="password" class="form-control"
                                                   placeholder="Laisser vide si inchangé">
                                            <span id="error_edit_password" class="text-danger small"></span>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Confirmation mot de passe</label>
                                            <input type="password" id="edit_password_confirmation" name="password_confirmation"
                                                   class="form-control" placeholder="Répétez le nouveau mot de passe">
                                            <span id="error_edit_password_confirmation" class="text-danger small"></span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Rôles <span class="text-danger">*</span></label>
                                            <select id="edit_roles" name="roles[]" class="form-select" multiple>
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                                @endforeach
                                            </select>
                                            <span id="error_edit_roles" class="text-danger small"></span>
                                        </div>
                                    </div>

                                    <div class="form-check">
                                        <input type="checkbox" id="edit_is_active" name="is_active" class="form-check-input">
                                        <label class="form-check-label" for="edit_is_active">Compte actif</label>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle"></i> Annuler
                                </button>
                                <button type="button" class="btn btn-warning" id="updateUserBtn">
                                    <i class="bi bi-save"></i> Modifier
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Détails Utilisateur -->
                <div class="modal fade" id="userViewModal" tabindex="-1" aria-labelledby="userViewModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-info text-white">
                                <h5 class="modal-title" id="userViewModalLabel">
                                    <i class="bi bi-person-badge"></i> Détails de l'Utilisateur
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <img id="view_photo" src="" class="img-thumbnail rounded-circle mb-3"
                                             style="width: 120px; height: 120px; object-fit: cover;"
                                             alt="Photo utilisateur">
                                    </div>
                                    <div class="col-md-9">
                                        <table class="table table-borderless">
                                            <tr>
                                                <th width="30%">Nom complet:</th>
                                                <td><span id="view_name" class="fw-bold"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Login:</th>
                                                <td><span id="view_login"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Email:</th>
                                                <td><span id="view_email"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Rôles:</th>
                                                <td><span id="view_roles" class="badges-container"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Statut:</th>
                                                <td><span id="view_status"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Attestations générées:</th>
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
                <div class="modal fade" id="userDeleteModal" tabindex="-1" aria-labelledby="userDeleteModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="userDeleteModalLabel">
                                    <i class="bi bi-trash"></i> Confirmer la Suppression
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="delete_user_id">
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Attention!</strong> Cette action est irréversible.
                                </div>
                                <p class="mb-0">Voulez-vous vraiment supprimer cet utilisateur ?</p>
                                <p class="text-muted small">Le compte sera désactivé et marqué comme supprimé.</p>
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

                <!-- Tableau des Utilisateurs -->
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="bi bi-people"></i> Liste des Utilisateurs
                            </h4>
                            @can('manage users')
                            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#userModal">
                                <i class="bi bi-person-plus"></i> Nouvel Utilisateur
                            </button>
                            @endcan
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-bordered" id="usersTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="5%">N°</th>
                                            <th width="20%">Utilisateur</th>
                                            <th width="15%">Login</th>
                                            <th width="20%">Email</th>
                                            <th width="15%">Rôles</th>
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
.photo-preview {
    max-width: 50px;
    max-height: 50px;
    border-radius: 5px;
}
</style>
@endsection

@section('scripts')

<script>
/**
 * Gestion des Utilisateurs - CRUD avec DataTables
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
        dataTable = $('#usersTable').DataTable({
            processing: true,
            serverSide: false,
            responsive: true,
            ajax: {
                url: '/users',
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
                    $('#usersTable tbody').html(
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
                    data: null,
                    render: function(data) {
                        const photo = data.photo || 'assets/images/default.jpg';
                        const photoUrl = photo.startsWith('http') ? photo : `/${photo}`;
                        return `
                            <div class="d-flex align-items-center">
                                <img src="${photoUrl}" class="rounded-circle me-3"
                                     style="width: 40px; height: 40px; object-fit: cover;"
                                     alt="${data.name}">
                                <div>
                                    <strong>${data.name}</strong>
                                </div>
                            </div>
                        `;
                    }
                },
                {
                    data: 'login',
                    className: 'align-middle'
                },
                {
                    data: 'email',
                    render: function(data) {
                        return data || '<span class="text-muted">Non renseigné</span>';
                    },
                    className: 'align-middle'
                },
                {
                    data: 'roles',
                    render: function(data) {
                        if (!data || data.length === 0) {
                            return '<span class="text-muted">Aucun rôle</span>';
                        }
                        let rolesHtml = '';
                        data.forEach(role => {
                            rolesHtml += `<span class="badge bg-primary me-1">${role.name}</span>`;
                        });
                        return rolesHtml;
                    },
                    className: 'align-middle'
                },
                {
                    data: 'is_active',
                    render: function(data) {
                        return data
                            ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Actif</span>'
                            : '<span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Inactif</span>';
                    },
                    className: 'text-center align-middle'
                },
                {
                    data: 'id',
                    render: function(data, type, row) {
                        const isCurrentUser = data === {{ auth()->id() }};
                        const deleteBtn = isCurrentUser
                            ? '<button class="btn btn-danger btn-sm" disabled title="Vous ne pouvez pas vous supprimer"><i class="bi bi-trash"></i></button>'
                            : `<button class="btn btn-danger btn-sm delete-btn" data-id="${data}" title="Supprimer"><i class="bi bi-trash"></i></button>`;

                        const toggleBtn = isCurrentUser
                            ? '<button class="btn btn-success btn-sm" disabled title="Vous ne pouvez pas modifier votre statut"><i class="bi bi-arrow-repeat"></i></button>'
                            : `<button class="btn btn-success btn-sm toggle-status-btn" data-id="${data}" data-status="${row.is_active}" title="Changer statut"><i class="bi bi-arrow-repeat"></i></button>`;

                        return `
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-info view-btn" data-id="${data}" title="Détails">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-warning edit-btn" data-id="${data}" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                ${deleteBtn}
                                ${toggleBtn}
                            </div>
                        `;
                    },
                    className: 'text-center align-middle',
                    orderable: false
                }
            ],
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

    // ============= AJOUT D'UTILISATEUR =============

    $('#saveUserBtn').on('click', function() {
        clearErrors();
        showSpinner();

        const formData = new FormData();
        formData.append('name', $('#name').val());
        formData.append('login', $('#login').val());
        formData.append('email', $('#email').val());
        formData.append('password', $('#password').val());
        formData.append('password_confirmation', $('#password_confirmation').val());
        formData.append('is_active', $('#is_active').is(':checked') ? 1 : 0);

        // Ajouter les rôles sélectionnés
        $('#roles option:selected').each(function() {
            formData.append('roles[]', $(this).val());
        });

        // Ajouter la photo si sélectionnée
        const photoFile = $('#photo')[0].files[0];
        if (photoFile) {
            formData.append('photo', photoFile);
        }

        $.ajax({
            url: '/users',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#userModal').modal('hide');
                    $('#userForm')[0].reset();
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
        const userId = $(this).data('id');
        showSpinner();

        $.ajax({
            url: `/users/${userId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const user = response.data;
                    const photoUrl = user.photo ? (user.photo.startsWith('http') ? user.photo : `/${user.photo}`) : '/assets/images/default.jpg';

                    $('#view_photo').attr('src', photoUrl);
                    $('#view_name').text(user.name);
                    $('#view_login').text(user.login);
                    $('#view_email').text(user.email || 'Non renseigné');

                    // Afficher les rôles
                    let rolesHtml = '';
                    if (user.roles && user.roles.length > 0) {
                        user.roles.forEach(role => {
                            rolesHtml += `<span class="badge bg-primary me-1 mb-1">${role.name}</span>`;
                        });
                    } else {
                        rolesHtml = '<span class="text-muted">Aucun rôle</span>';
                    }
                    $('#view_roles').html(rolesHtml);

                    $('#view_status').html(user.is_active
                        ? '<span class="badge bg-success">Actif</span>'
                        : '<span class="badge bg-secondary">Inactif</span>');
                    $('#view_attestations_count').text(user.attestations_count || 0);
                    $('#view_created_at').text(new Date(user.created_at).toLocaleDateString('fr-FR'));
                    $('#view_updated_at').text(new Date(user.updated_at).toLocaleDateString('fr-FR'));

                    $('#userViewModal').modal('show');
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
        const userId = $(this).data('id');
        showSpinner();

        $.ajax({
            url: `/users/${userId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const user = response.data;
                    const photoUrl = user.photo ? (user.photo.startsWith('http') ? user.photo : `/${user.photo}`) : '/assets/images/default.jpg';

                    $('#edit_user_id').val(user.id);
                    $('#edit_name').val(user.name);
                    $('#edit_login').val(user.login);
                    $('#edit_email').val(user.email || '');
                    $('#edit_is_active').prop('checked', user.is_active);

                    // Sélectionner les rôles
                    $('#edit_roles').val([]);
                    if (user.roles && user.roles.length > 0) {
                        const roleNames = user.roles.map(role => role.name);
                        $('#edit_roles').val(roleNames);
                    }

                    // Afficher la photo actuelle
                    $('#current_photo').html(`
                        <div class="d-flex align-items-center">
                            <img src="${photoUrl}" class="photo-preview me-2" width=50 alt="Photo actuelle">
                            <small class="text-muted">Photo actuelle</small>
                        </div>
                    `);

                    $('#userEditModal').modal('show');
                }
                hideSpinner();
            },
            error: function(xhr) {
                hideSpinner();
                showToast('Erreur lors du chargement des données', 'error');
            }
        });
    });

    $('#updateUserBtn').on('click', function() {
        clearErrors();
        showSpinner();

        const userId = $('#edit_user_id').val();
        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('name', $('#edit_name').val());
        formData.append('login', $('#edit_login').val());
        formData.append('email', $('#edit_email').val());
        formData.append('is_active', $('#edit_is_active').is(':checked') ? 1 : 0);

        // Ajouter le mot de passe seulement s'il est rempli
        const password = $('#edit_password').val();
        if (password) {
            formData.append('password', password);
            formData.append('password_confirmation', $('#edit_password_confirmation').val());
        }

        // Ajouter les rôles
        $('#edit_roles option:selected').each(function() {
            formData.append('roles[]', $(this).val());
        });

        // Ajouter la nouvelle photo si sélectionnée
        const photoFile = $('#edit_photo')[0].files[0];
        if (photoFile) {
            formData.append('photo', photoFile);
        }

        $.ajax({
            url: `/users/${userId}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#userEditModal').modal('hide');
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
        const userId = $(this).data('id');
        $('#delete_user_id').val(userId);
        $('#userDeleteModal').modal('show');
    });

    $('#confirmDeleteBtn').on('click', function() {
        showSpinner();
        const userId = $('#delete_user_id').val();

        $.ajax({
            url: `/users/${userId}`,
            method: 'DELETE',
            success: function(response) {
                if (response.success) {
                    $('#userDeleteModal').modal('hide');
                    dataTable.ajax.reload();
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
        const userId = $(this).data('id');
        showSpinner();

        const formData = new FormData();
        formData.append('_method', 'PATCH');

        $.ajax({
            url: `/users/${userId}/toggle-status`,
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

    // ============= RÉINITIALISER LES FORMULAIRES =============

    $('#userModal').on('hidden.bs.modal', function() {
        $('#userForm')[0].reset();
        clearErrors();
    });

    $('#userEditModal').on('hidden.bs.modal', function() {
        $('#userEditForm')[0].reset();
        $('#current_photo').empty();
        clearErrors();
    });
});
</script>
@endsection

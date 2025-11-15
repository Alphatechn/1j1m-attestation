/**
 * Gestion des Périodes - CRUD avec AJAX (Version Simple)
 */

$(document).ready(function() {
    // Variables globales
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Charger les périodes au démarrage
    loadPeriodes();

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

    // ============= CHARGEMENT DES PÉRIODES =============

    function loadPeriodes() {
        showSpinner();

        $.ajax({
            url: '/periodes',
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {
                if (response.success) {
                    renderPeriodesTable(response.data.data);
                }
                hideSpinner();
            },
            error: function(xhr) {
                console.error('Erreur lors du chargement:', xhr);
                showToast('Erreur lors du chargement des périodes', 'error');
                $('#periodesData').html('<tr><td colspan="7" class="text-center text-danger">Erreur de chargement</td></tr>');
                hideSpinner();
            }
        });
    }

    function renderPeriodesTable(periodes) {
        let html = '';

        if (periodes.length === 0) {
            html = '<tr><td colspan="7" class="text-center text-muted py-4">Aucune période trouvée</td></tr>';
        } else {
            $.each(periodes, function(index, periode) {
                const statusBadge = periode.is_active
                    ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>'
                    : '<span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Inactive</span>';

                html += `
                    <tr>
                        <td class="fw-bold text-center">${index + 1}</td>
                        <td>
                            <strong>${periode.libelle}</strong>
                            ${periode.description ? `<br><small class="text-muted">${periode.description}</small>` : ''}
                        </td>
                        <td><small>${periode.mois_debut}/${periode.annee_debut}</small></td>
                        <td><small>${periode.mois_fin}/${periode.annee_fin}</small></td>
                        <td class="text-center">
                            <span class="badge bg-primary">${periode.participants_count || 0}</span>
                        </td>
                        <td class="text-center">${statusBadge}</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-info view-btn" data-id="${periode.id}" title="Détails">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-warning edit-btn" data-id="${periode.id}" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-danger delete-btn" data-id="${periode.id}" title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <button class="btn btn-success toggle-status-btn" data-id="${periode.id}"
                                        data-status="${periode.is_active}" title="Changer statut">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }

        $('#periodesData').html(html);
    }

    // ============= AJOUT DE PÉRIODE =============

    $('#savePeriodeBtn').on('click', function() {
        clearErrors();
        showSpinner();

        const formData = {
            libelle: $('#libelle').val(),
            mois_debut: $('#mois_debut').val(),
            annee_debut: $('#annee_debut').val(),
            mois_fin: $('#mois_fin').val(),
            annee_fin: $('#annee_fin').val(),
            description: $('#description').val(),
            is_active: $('#is_active').is(':checked') ? 1 : 0
        };

        $.ajax({
            url: '/periodes',
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    $('#periodeModal').modal('hide');
                    $('#periodeForm')[0].reset();
                    loadPeriodes();
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
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
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
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
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
        const formData = {
            libelle: $('#edit_libelle').val(),
            mois_debut: $('#edit_mois_debut').val(),
            annee_debut: $('#edit_annee_debut').val(),
            mois_fin: $('#edit_mois_fin').val(),
            annee_fin: $('#edit_annee_fin').val(),
            description: $('#edit_description').val(),
            is_active: $('#edit_is_active').is(':checked') ? 1 : 0
        };

        $.ajax({
            url: `/periodes/${periodeId}`,
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    $('#periodeEditModal').modal('hide');
                    loadPeriodes();
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
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {
                if (response.success) {
                    $('#periodeDeleteModal').modal('hide');
                    loadPeriodes();
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

        $.ajax({
            url: `/periodes/${periodeId}/toggle-status`,
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {
                if (response.success) {
                    loadPeriodes();
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

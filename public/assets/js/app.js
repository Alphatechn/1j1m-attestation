/**
 * Fonctions utilitaires globales
 */

// Configuration CSRF pour toutes les requêtes AJAX
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

if (csrfToken) {
    // Configuration pour fetch
    window.fetchWithCSRF = function(url, options = {}) {
        options.headers = {
            ...options.headers,
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        };
        return fetch(url, options);
    };

    // Configuration pour jQuery AJAX
    if (typeof $ !== 'undefined') {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
    }
}

/**
 * Afficher une alerte SweetAlert2
 */
function showAlert(title, text, icon = 'info') {
    return Swal.fire({
        title: title,
        text: text,
        icon: icon,
        confirmButtonColor: '#0d6efd',
        confirmButtonText: 'OK'
    });
}

/**
 * Afficher une confirmation
 */
function showConfirm(title, text, confirmText = 'Oui', cancelText = 'Non') {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: confirmText,
        cancelButtonText: cancelText
    });
}

/**
 * Afficher un toast de notification
 */
function showToast(message, icon = 'success') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    Toast.fire({
        icon: icon,
        title: message
    });
}

/**
 * Afficher/masquer le spinner de chargement
 */
function toggleLoader(show = true) {
    let loader = document.getElementById('globalLoader');

    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'globalLoader';
        loader.className = 'spinner-overlay';
        loader.innerHTML = `
            <div class="spinner-border text-light spinner-border-lg" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
        `;
        document.body.appendChild(loader);
    }

    loader.style.display = show ? 'flex' : 'none';
}

/**
 * Formater une date
 */
function formatDate(dateString, format = 'dd/mm/yyyy') {
    if (!dateString) return '';

    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return format
        .replace('dd', day)
        .replace('mm', month)
        .replace('yyyy', year)
        .replace('HH', hours)
        .replace('MM', minutes);
}

/**
 * Gérer les erreurs de validation
 */
function displayValidationErrors(errors) {
    // Effacer les erreurs précédentes
    document.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    document.querySelectorAll('.invalid-feedback').forEach(el => {
        el.remove();
    });

    // Afficher les nouvelles erreurs
    Object.keys(errors).forEach(field => {
        const input = document.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('is-invalid');

            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback d-block';
            feedback.textContent = errors[field][0];
            input.parentNode.appendChild(feedback);
        }
    });
}

/**
 * Effacer les erreurs de validation
 */
function clearValidationErrors() {
    document.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    document.querySelectorAll('.invalid-feedback').forEach(el => {
        el.remove();
    });
}

/**
 * Gérer la réponse AJAX
 */
async function handleAjaxResponse(response) {
    const data = await response.json();

    if (!response.ok) {
        if (response.status === 422 && data.errors) {
            displayValidationErrors(data.errors);
        }
        throw new Error(data.message || 'Une erreur est survenue');
    }

    return data;
}

/**
 * Soumettre un formulaire en AJAX
 */
async function submitForm(formId, url, method = 'POST') {
    const form = document.getElementById(formId);
    if (!form) return;

    clearValidationErrors();
    toggleLoader(true);

    try {
        const formData = new FormData(form);

        const response = await fetchWithCSRF(url, {
            method: method,
            body: formData
        });

        const data = await handleAjaxResponse(response);

        if (data.success) {
            showToast(data.message, 'success');
            return data;
        }
    } catch (error) {
        showToast(error.message, 'error');
        throw error;
    } finally {
        toggleLoader(false);
    }
}

/**
 * Confirmer une action de suppression
 */
async function confirmDelete(url, redirectUrl = null) {
    const result = await showConfirm(
        'Êtes-vous sûr?',
        'Cette action est irréversible!',
        'Oui, supprimer',
        'Annuler'
    );

    if (result.isConfirmed) {
        toggleLoader(true);

        try {
            const response = await fetchWithCSRF(url, {
                method: 'DELETE'
            });

            const data = await handleAjaxResponse(response);

            if (data.success) {
                showToast(data.message, 'success');

                if (redirectUrl) {
                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 1500);
                }

                return true;
            }
        } catch (error) {
            showToast(error.message, 'error');
        } finally {
            toggleLoader(false);
        }
    }

    return false;
}

/**
 * Initialisation au chargement de la page
 */
document.addEventListener('DOMContentLoaded', function() {
    // Auto-fermeture des alertes après 5 secondes
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Popovers Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

/**
 * Exporter les fonctions pour utilisation globale
 */
window.app = {
    showAlert,
    showConfirm,
    showToast,
    toggleLoader,
    formatDate,
    displayValidationErrors,
    clearValidationErrors,
    handleAjaxResponse,
    submitForm,
    confirmDelete
};

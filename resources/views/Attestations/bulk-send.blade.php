@extends('layouts.front')

@section('title', 'Envoi Massif d\'Attestations')

@section('content')

<div class="container-fluid px-4">
    <h1 class="mt-4">📤 Envoi Massif d'Attestations</h1>

    {{-- Alertes --}}
    <div id="alert-container"></div>

    {{-- Stats Globales --}}
    <div class="row mt-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h5>{{ $stats['total_participants'] }}</h5>
                    <small>Participants Total</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h5>{{ $stats['with_attestations'] }}</h5>
                    <small>Avec Attestations</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h5>{{ $stats['without_attestations'] }}</h5>
                    <small>Sans Attestations</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h5 id="pending-jobs-count">{{ $stats['pending_jobs'] }}</h5>
                    <small>Jobs en Attente</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Statut en Temps Réel --}}
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-area me-1"></i>
            Statut en Temps Réel
            <button class="btn btn-sm btn-primary float-end" onclick="refreshStatus()">
                <i class="fas fa-sync"></i> Actualiser
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h6>📊 Quota Email (Hostinger)</h6>
                    <div class="progress mb-2">
                        <div id="quota-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                    <p id="quota-text" class="mb-0">Chargement...</p>
                </div>
                <div class="col-md-4">
                    <h6>⏰ Timing</h6>
                    <p id="timing-text">Chargement...</p>
                </div>
                <div class="col-md-4">
                    <h6>🚀 File d'Attente</h6>
                    <p id="queue-text">Chargement...</p>
                </div>
            </div>
            <div id="hostinger-block" class="alert alert-danger mt-3" style="display: none;">
                <strong>🚨 HOSTINGER BLOQUÉ</strong>
                <p class="mb-0" id="hostinger-block-text"></p>
                <button class="btn btn-warning btn-sm mt-2" onclick="unlockHostinger()">
                    <i class="fas fa-unlock"></i> Débloquer Maintenant
                </button>
            </div>
        </div>
    </div>

    {{-- Formulaire d'Envoi Massif --}}
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-paper-plane me-1"></i>
            Planifier un Envoi Massif
        </div>
        <div class="card-body">
            <form id="bulk-send-form">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="periode_id" class="form-label">Période (optionnel)</label>
                        <select class="form-select" id="periode_id" name="periode_id">
                            <option value="">-- Toutes les périodes --</option>
                            @foreach($periodes as $periode)
                                <option value="{{ $periode->id }}">
                                    {{ $periode->full_libelle }}
                                    ({{ $periode->participants_count }} sans attestation)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="limit" class="form-label">Limite</label>
                        <input type="number" class="form-control" id="limit" name="limit"
                               value="20" min="1" max="100">
                        <small class="text-muted">Maximum: 100 (recommandé: 20)</small>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-info" onclick="previewBulkSend()">
                        <i class="fas fa-eye"></i> 1️⃣ Prévisualiser
                    </button>
                    <button type="button" class="btn btn-success" onclick="confirmBulkSend()"
                            id="send-btn" disabled>
                        <i class="fas fa-paper-plane"></i> 2️⃣ Envoyer en Masse
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Prévisualisation --}}
    <div class="card mb-4" id="preview-card" style="display: none;">
        <div class="card-header bg-info text-white">
            <i class="fas fa-list me-1"></i>
            Prévisualisation
        </div>
        <div class="card-body">
            <div id="preview-content"></div>
        </div>
    </div>

    {{-- Actions Rapides --}}
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-tools me-1"></i>
            Actions Rapides
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <button class="btn btn-warning w-100" onclick="cancelPendingJobs()">
                        <i class="fas fa-ban"></i> Annuler les Envois en Attente
                    </button>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-danger w-100" onclick="retryFailedJobs()">
                        <i class="fas fa-redo"></i> Réessayer les Échecs
                    </button>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-dark w-100" onclick="unlockHostinger()">
                        <i class="fas fa-unlock-alt"></i> Réinitialiser Hostinger
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Historique Récent --}}
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-history me-1"></i>
            Derniers Envois
        </div>
        <div class="card-body">
            <div id="recent-sends" class="table-responsive">
                <p class="text-muted">Chargement...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let previewData = null;
let statusRefreshTimer = null;
let statusRefreshInFlight = false;
let statusRefreshDelay = 30000;

// Rafraîchir le statut sans empiler les requêtes si le serveur répond lentement.
refreshStatus();

// ========================================
// FONCTIONS
// ========================================

function refreshStatus() {
    if (statusRefreshInFlight) {
        return;
    }

    clearTimeout(statusRefreshTimer);
    statusRefreshInFlight = true;

    fetch('{{ route("attestations.bulk.status") }}', {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
    })
        .then(async response => {
            if (!response.ok) {
                const retryAfterHeader = response.headers.get('Retry-After');
                let retryAfter = Number(retryAfterHeader);
                if (retryAfterHeader && !Number.isFinite(retryAfter)) {
                    retryAfter = (Date.parse(retryAfterHeader) - Date.now()) / 1000;
                }
                const error = new Error(`HTTP ${response.status} lors de la récupération du statut`);
                error.status = response.status;
                error.retryAfter = Number.isFinite(retryAfter) && retryAfter > 0
                    ? retryAfter * 1000
                    : null;
                throw error;
            }

            return response.json();
        })
        .then(data => {
            if (!data.success || !data.data) {
                throw new Error(data.message || 'Réponse de statut invalide');
            }
            updateStatusDisplay(data.data);
            statusRefreshDelay = 30000;
        })
        .catch(err => {
            if (err.status === 429 && err.retryAfter) {
                statusRefreshDelay = Math.min(err.retryAfter, 600000);
            } else {
                statusRefreshDelay = err.status === 429
                    ? Math.min(Math.max(statusRefreshDelay * 2, 60000), 120000)
                    : Math.min(statusRefreshDelay * 2, 120000);
            }
            console.error('Erreur refresh status:', err);
        })
        .finally(() => {
            statusRefreshInFlight = false;
            statusRefreshTimer = setTimeout(refreshStatus, statusRefreshDelay);
        });
}

function getStatusElement(id) {
    const element = document.getElementById(id);
    if (!element) {
        throw new Error(`Élément de statut introuvable: #${id}`);
    }
    return element;
}

function getHostingerBlockElements() {
    let block = document.getElementById('hostinger-block');
    if (!block) {
        const queueText = getStatusElement('queue-text');
        const cardBody = queueText.closest('.card-body');
        if (!cardBody) {
            throw new Error('Conteneur du statut Hostinger introuvable');
        }

        block = document.createElement('div');
        block.id = 'hostinger-block';
        block.className = 'alert alert-danger mt-3';
        block.style.display = 'none';

        const title = document.createElement('strong');
        title.textContent = '🚨 HOSTINGER BLOQUÉ';

        const text = document.createElement('p');
        text.id = 'hostinger-block-text';
        text.className = 'mb-0';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-warning btn-sm mt-2';
        button.innerHTML = '<i class="fas fa-unlock"></i> Débloquer Maintenant';
        button.addEventListener('click', unlockHostinger);

        block.append(title, text, button);
        cardBody.appendChild(block);
        console.warn('Bloc Hostinger absent du HTML; élément recréé dans le statut.');
    }

    let text = block.querySelector('#hostinger-block-text');
    if (!text) {
        text = document.createElement('p');
        text.id = 'hostinger-block-text';
        text.className = 'mb-0';
        block.prepend(text);
        console.warn('Texte du bloc Hostinger absent du HTML; élément recréé.');
    }

    return { block, text };
}

function updateStatusDisplay(status) {
    // Quota
    const quota = status.quota;
    const percentage = Math.min(quota.percentage, 100);
    const quotaBar = getStatusElement('quota-bar');
    quotaBar.style.width = percentage + '%';
    quotaBar.className = 'progress-bar ' + (percentage > 80 ? 'bg-danger' : percentage > 50 ? 'bg-warning' : 'bg-success');
    getStatusElement('quota-text').textContent =
        `${quota.sent_this_hour} / ${quota.max_per_hour} envoyés (${quota.remaining} restants)`;

    // Timing
    getStatusElement('timing-text').innerHTML = `
        Dernier envoi: ${status.timing.last_email_ago_human || 'Jamais'}<br>
        <span class="${status.timing.can_send_now ? 'text-success' : 'text-warning'}">
            ${status.timing.can_send_now ? '✅ Peut envoyer maintenant' : '⏳ Prochain: ' + status.timing.next_available_at}
        </span>
    `;

    // Queue
    getStatusElement('queue-text').innerHTML = `
        En attente: ${status.queue.pending_jobs}<br>
        Échecs: ${status.queue.failed_jobs}<br>
        Attestations pending: ${status.queue.pending_attestations}
    `;
    getStatusElement('pending-jobs-count').textContent = status.queue.pending_jobs;

    // Hostinger bloqué ?
    const hostingerBlock = getHostingerBlockElements();
    if (status.hostinger.blocked) {
        hostingerBlock.block.style.display = 'block';
        hostingerBlock.text.textContent =
            `Déblocage prévu à ${status.hostinger.blocked_until} (dans ${status.hostinger.blocked_remaining})`;
    } else {
        hostingerBlock.block.style.display = 'none';
    }

    // Recent
    if (status.recent && status.recent.length > 0) {
        let html = '<table class="table table-sm"><thead><tr><th>Participant</th><th>Email</th><th>Envoyé</th></tr></thead><tbody>';
        status.recent.forEach(item => {
            html += `<tr><td>${item.participant}</td><td>${item.email}</td><td>${item.sent_at}</td></tr>`;
        });
        html += '</tbody></table>';
        getStatusElement('recent-sends').innerHTML = html;
    }
}

function unlockHostinger() {
    if (!confirm('⚠️ Êtes-vous sûr de vouloir réinitialiser tous les verrous Hostinger ?\n\nCela va :\n- Débloquer Hostinger\n- Réinitialiser le compteur de quota\n- Supprimer tous les locks\n- Réinitialiser le timestamp')) {
        return;
    }

    showAlert('info', '🔄 Réinitialisation en cours...');

    fetch('{{ route("admin.unlock-hostinger") }}', {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            let reportHtml = '<div class="alert alert-success"><h5>✅ ' + data.message + '</h5>';

            // Rapport détaillé
            reportHtml += '<hr><h6>📊 Rapport détaillé :</h6><ul class="mb-0">';
            reportHtml += `<li><strong>Hostinger:</strong> ${data.report.hostinger}</li>`;
            reportHtml += `<li><strong>Timestamp:</strong> ${data.report.timestamp}</li>`;
            reportHtml += `<li><strong>Quota:</strong> ${data.report.quota}</li>`;
            reportHtml += `<li><strong>Locks:</strong> ${data.report.locks}</li>`;
            reportHtml += '</ul>';

            // Prochaines étapes
            reportHtml += '<hr><h6>🔔 Prochaines étapes :</h6><ol class="mb-0">';
            data.next_steps.forEach(step => {
                reportHtml += `<li>${step}</li>`;
            });
            reportHtml += '</ol></div>';

            showAlert('success', reportHtml);

            // Rafraîchir le statut après 1 seconde
            setTimeout(refreshStatus, 1000);
        } else {
            showAlert('danger', '❌ ' + data.message);
        }
    })
    .catch(err => {
        showAlert('danger', '❌ Erreur lors de la réinitialisation: ' + err.message);
    });
}

function previewBulkSend() {
    showAlert('info', 'Chargement de la prévisualisation...');

    const formData = new FormData(document.getElementById('bulk-send-form'));

    fetch('{{ route("attestations.bulk.preview") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            previewData = data.data;
            displayPreview(data.data);
            document.getElementById('send-btn').disabled = false;
            showAlert('success', `✅ ${data.data.total_valid} participant(s) valide(s) trouvé(s)`);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(err => {
        showAlert('danger', 'Erreur lors de la prévisualisation: ' + err.message);
    });
}

function displayPreview(data) {
    let html = `
        <div class="alert alert-info">
            <strong>📊 Résumé:</strong><br>
            ✅ Valides: ${data.total_valid}<br>
            ❌ Invalides: ${data.total_invalid}<br>
            ⏱️ Durée estimée: ${data.estimated_duration}
        </div>
    `;

    if (data.valid.length > 0) {
        html += '<h6 class="text-success">Participants Valides:</h6>';
        html += '<div class="table-responsive"><table class="table table-sm table-striped">';
        html += '<thead><tr><th>Nom</th><th>Email</th><th>Période</th></tr></thead><tbody>';
        data.valid.forEach(p => {
            html += `<tr><td>${p.full_name}</td><td>${p.email}</td><td>${p.periode}</td></tr>`;
        });
        html += '</tbody></table></div>';
    }

    if (data.invalid.length > 0) {
        html += '<h6 class="text-danger mt-3">Participants Invalides:</h6>';
        html += '<div class="table-responsive"><table class="table table-sm table-danger">';
        html += '<thead><tr><th>Nom</th><th>Email</th><th>Raison</th></tr></thead><tbody>';
        data.invalid.forEach(p => {
            html += `<tr><td>${p.full_name}</td><td>${p.email}</td><td>${p.reason}</td></tr>`;
        });
        html += '</tbody></table></div>';
    }

    document.getElementById('preview-content').innerHTML = html;
    document.getElementById('preview-card').style.display = 'block';
}

function confirmBulkSend() {
    if (!previewData) {
        showAlert('warning', 'Veuillez d\'abord prévisualiser.');
        return;
    }

    if (!confirm(`Confirmer l'envoi de ${previewData.total_valid} attestation(s) ?\n\nDurée estimée: ${previewData.estimated_duration}`)) {
        return;
    }

    document.getElementById('send-btn').disabled = true;
    showAlert('info', '🚀 Planification en cours...');

    const formData = new FormData(document.getElementById('bulk-send-form'));

    fetch('{{ route("attestations.bulk.send") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('success', `✅ ${data.data.success_count} attestation(s) planifiée(s) !<br>
                ⏱️ Durée: ${data.data.estimated_duration}<br>
                📅 Fin prévue: ${data.data.estimated_completion}`);

            previewData = null;
            document.getElementById('send-btn').disabled = true;
            document.getElementById('preview-card').style.display = 'none';
            refreshStatus();
        } else {
            showAlert('danger', data.message);
            document.getElementById('send-btn').disabled = false;
        }
    })
    .catch(err => {
        showAlert('danger', 'Erreur: ' + err.message);
        document.getElementById('send-btn').disabled = false;
    });
}

function cancelPendingJobs() {
    if (!confirm('Annuler tous les envois en attente ?')) {
        return;
    }

    fetch('{{ route("attestations.bulk.cancel") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            refreshStatus();
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(err => showAlert('danger', 'Erreur: ' + err.message));
}

function retryFailedJobs() {
    if (!confirm('Réessayer tous les jobs échoués ?')) {
        return;
    }

    fetch('{{ route("attestations.bulk.retry") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            refreshStatus();
        } else {
            showAlert('warning', data.message);
        }
    })
    .catch(err => showAlert('danger', 'Erreur: ' + err.message));
}

function showAlert(type, message) {
    const alertHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    document.getElementById('alert-container').innerHTML = alertHTML;

    // Auto-dismiss après 8 secondes pour les messages de succès détaillés
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) alert.remove();
    }, type === 'success' ? 8000 : 5000);
}
</script>

@endsection

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
            <button class="btn btn-warning" onclick="cancelPendingJobs()">
                <i class="fas fa-ban"></i> Annuler les Envois en Attente
            </button>
            <button class="btn btn-danger" onclick="retryFailedJobs()">
                <i class="fas fa-redo"></i> Réessayer les Échecs
            </button>
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

@push('scripts')
<script>
let previewData = null;

// Rafraîchir le statut toutes les 10 secondes
setInterval(refreshStatus, 10000);
refreshStatus();

// ========================================
// FONCTIONS
// ========================================

function refreshStatus() {
    fetch('{{ route("attestations.bulk.status") }}')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateStatusDisplay(data.data);
            }
        })
        .catch(err => console.error('Erreur refresh status:', err));
}

function updateStatusDisplay(status) {
    // Quota
    const quota = status.quota;
    const percentage = Math.min(quota.percentage, 100);
    const quotaBar = document.getElementById('quota-bar');
    quotaBar.style.width = percentage + '%';
    quotaBar.className = 'progress-bar ' + (percentage > 80 ? 'bg-danger' : percentage > 50 ? 'bg-warning' : 'bg-success');
    document.getElementById('quota-text').textContent =
        `${quota.sent_this_hour} / ${quota.max_per_hour} envoyés (${quota.remaining} restants)`;

    // Timing
    document.getElementById('timing-text').innerHTML = `
        Dernier envoi: ${status.timing.last_email_ago_human || 'Jamais'}<br>
        <span class="${status.timing.can_send_now ? 'text-success' : 'text-warning'}">
            ${status.timing.can_send_now ? '✅ Peut envoyer maintenant' : '⏳ Prochain: ' + status.timing.next_available_at}
        </span>
    `;

    // Queue
    document.getElementById('queue-text').innerHTML = `
        En attente: ${status.queue.pending_jobs}<br>
        Échecs: ${status.queue.failed_jobs}<br>
        Attestations pending: ${status.queue.pending_attestations}
    `;
    document.getElementById('pending-jobs-count').textContent = status.queue.pending_jobs;

    // Hostinger bloqué ?
    if (status.hostinger.blocked) {
        document.getElementById('hostinger-block').style.display = 'block';
        document.getElementById('hostinger-block-text').textContent =
            `Déblocage prévu à ${status.hostinger.blocked_until} (dans ${status.hostinger.blocked_remaining})`;
    } else {
        document.getElementById('hostinger-block').style.display = 'none';
    }

    // Recent
    if (status.recent && status.recent.length > 0) {
        let html = '<table class="table table-sm"><thead><tr><th>Participant</th><th>Email</th><th>Envoyé</th></tr></thead><tbody>';
        status.recent.forEach(item => {
            html += `<tr><td>${item.participant}</td><td>${item.email}</td><td>${item.sent_at}</td></tr>`;
        });
        html += '</tbody></table>';
        document.getElementById('recent-sends').innerHTML = html;
    }
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

    // Auto-dismiss après 5 secondes
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) alert.remove();
    }, 5000);
}
</script>
@endpush

@endsection

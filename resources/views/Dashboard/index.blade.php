@extends('layouts.front')

@section('title', 'Dashboard')

@section('content')

    <!--begin::App Content Header-->
    <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Dashboard</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                    </ol>
                </div>
            </div>
            <!--end::Row-->
        </div>
        <!--end::Container-->
    </div>

    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">

            <!-- Info boxes -->
            <div class="row">
                <!-- Utilisateurs -->
                <div class="col-12 col-sm-6 col-md-3 mb-3">
                    <div class="info-box">
                        <span class="info-box-icon text-bg-primary shadow-sm">
                            <i class="bi bi-people-fill"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Utilisateurs</span>
                            <span class="info-box-number" id="totalUsers">
                                <span class="spinner-border spinner-border-sm"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Périodes -->
                <div class="col-12 col-sm-6 col-md-3 mb-3">
                    <div class="info-box">
                        <span class="info-box-icon text-bg-info shadow-sm">
                            <i class="bi bi-calendar-range-fill"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Périodes Actives</span>
                            <span class="info-box-number" id="activePeriodes">
                                <span class="spinner-border spinner-border-sm"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Participants -->
                <div class="col-12 col-sm-6 col-md-3 mb-3">
                    <div class="info-box">
                        <span class="info-box-icon text-bg-success shadow-sm">
                            <i class="bi bi-person-badge-fill"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Participants</span>
                            <span class="info-box-number" id="totalParticipants">
                                <span class="spinner-border spinner-border-sm"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Attestations -->
                <div class="col-12 col-sm-6 col-md-3 mb-3">
                    <div class="info-box">
                        <span class="info-box-icon text-bg-warning shadow-sm">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Attestations</span>
                            <span class="info-box-number" id="totalAttestations">
                                <span class="spinner-border spinner-border-sm"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques supplémentaires -->
            <div class="row">
                <div class="col-md-3 col-6 mb-3">
                    <div class="card text-bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Attestations Envoyées</h5>
                            <p class="card-text h3" id="sentAttestations">0</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6 mb-3">
                    <div class="card text-bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">En Attente</h5>
                            <p class="card-text h3" id="pendingAttestations">0</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6 mb-3">
                    <div class="card text-bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Participants Certifiés</h5>
                            <p class="card-text h3" id="certifiedParticipants">0</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6 mb-3">
                    <div class="card text-bg-danger">
                        <div class="card-body">
                            <h5 class="card-title">Vues Totales</h5>
                            <p class="card-text h3" id="totalViews">0</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="row">
                <!-- Graphique mensuel -->
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Évolution des Attestations (6 derniers mois)</h5>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                                    <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                                    <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="monthlyChart"></div>
                        </div>
                    </div>
                </div>

                <!-- Top 5 Périodes -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-trophy-fill"></i> Top 5 Périodes
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0" id="topPeriodesTable">
                                    <thead>
                                        <tr>
                                            <th>Période</th>
                                            <th class="text-center">Participants</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="2" class="text-center">
                                                <span class="spinner-border spinner-border-sm"></span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attestations récentes et Participants sans attestation -->
            <div class="row">
                <!-- Attestations récentes -->
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Attestations Récentes</h5>
                            <div class="card-tools">
                                <a href="{{ route('attestations.index') }}" class="btn btn-sm btn-primary">
                                    Voir tout
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>N° Attestation</th>
                                            <th>Participant</th>
                                            <th>Période</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recentAttestationsTable">
                                        <tr>
                                            <td colspan="6" class="text-center">
                                                <span class="spinner-border spinner-border-sm"></span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Participants sans attestation -->
                <div class="col-md-4 mb-4">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Sans Attestation
                            <span class="badge bg-danger float-end" id="noAttestationCount">0</span>
                        </div>
                        <div class="card-body p-2">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0" id="noAttestationTable">
                                    <thead>
                                        <tr>
                                            <th>Participant</th>
                                            <th>Période</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="2" class="text-center">
                                                <span class="spinner-border spinner-border-sm"></span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activité récente (7 derniers jours) -->
            @can('view statistics')
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Activité des 7 derniers jours</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="border p-3 rounded">
                                        <h6 class="text-muted">Nouveaux Participants</h6>
                                        <h3 class="mb-0" id="newParticipants">0</h3>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border p-3 rounded">
                                        <h6 class="text-muted">Nouvelles Attestations</h6>
                                        <h3 class="mb-0" id="newAttestations">0</h3>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border p-3 rounded">
                                        <h6 class="text-muted">Attestations Envoyées</h6>
                                        <h3 class="mb-0" id="sentThisWeek">0</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endcan

        </div>
        <!--end::Container-->
    </div>
    <!--end::App Content-->

@endsection

@section('scripts')
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>
        $(document).ready(function() {
            // Vérifier l'authentification
            @guest
                window.location.href = '{{ route('login') }}';
                return;
            @endguest

            // Charger les statistiques
            loadDashboardStats();
            loadRecentAttestations();
            loadParticipantsWithoutAttestation();

            // Fonction principale pour charger les statistiques
            function loadDashboardStats() {
                $.ajax({
                    url: "{{ route('dashboard.stats') }}",
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            updateStatsBoxes(response.data);
                            renderMonthlyChart(response.data.monthly);
                            renderTopPeriodes(response.data.top_periodes);
                            updateRecentActivity(response.data.recent_activity);
                        }
                    },
                    error: function(xhr) {
                        console.error('Erreur lors du chargement des statistiques:', xhr);
                        showToast('Erreur lors du chargement des statistiques', 'error');
                    }
                });
            }

            // Mettre à jour les boîtes de statistiques
            function updateStatsBoxes(data) {
                $('#totalUsers').text(data.general.users.total);
                $('#activePeriodes').text(data.general.periodes.active);
                $('#totalParticipants').text(data.general.participants.total);
                $('#totalAttestations').text(data.general.attestations.total);
                $('#sentAttestations').text(data.general.attestations.sent);
                $('#pendingAttestations').text(data.general.attestations.pending);
                $('#certifiedParticipants').text(data.general.participants.with_attestation);
                $('#totalViews').text(data.general.attestations.total_views.toLocaleString());
            }

            // Rendre le graphique mensuel
            function renderMonthlyChart(monthlyData) {
                const options = {
                    series: [{
                        name: 'Participants',
                        data: monthlyData.map(item => item.participants)
                    }, {
                        name: 'Attestations',
                        data: monthlyData.map(item => item.attestations)
                    }],
                    chart: {
                        height: 300,
                        type: 'area',
                        toolbar: {
                            show: false
                        }
                    },
                    legend: {
                        show: true,
                        position: 'top'
                    },
                    colors: ['#0d6efd', '#20c997'],
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 2
                    },
                    xaxis: {
                        categories: monthlyData.map(item => item.month)
                    },
                    tooltip: {
                        shared: true,
                        intersect: false
                    }
                };

                const chart = new ApexCharts(document.querySelector('#monthlyChart'), options);
                chart.render();
            }

            // Afficher les top périodes
            function renderTopPeriodes(topPeriodes) {
                let html = '';

                if (topPeriodes.length === 0) {
                    html = '<tr><td colspan="2" class="text-center text-muted">Aucune donnée</td></tr>';
                } else {
                    topPeriodes.forEach((periode, index) => {
                        const badge = index === 0 ? 'bg-warning' : index === 1 ? 'bg-secondary' : index === 2 ? 'bg-info' : 'bg-light text-dark';
                        html += `
                            <tr>
                                <td>
                                    <span class="badge ${badge} me-1">${index + 1}</span>
                                    ${periode.libelle}
                                </td>
                                <td class="text-center">
                                    <strong>${periode.count}</strong>
                                </td>
                            </tr>
                        `;
                    });
                }

                $('#topPeriodesTable tbody').html(html);
            }

            // Mettre à jour l'activité récente
            function updateRecentActivity(activity) {
                $('#newParticipants').text(activity.new_participants);
                $('#newAttestations').text(activity.new_attestations);
                $('#sentThisWeek').text(activity.sent_attestations);
            }

            // Charger les attestations récentes
            function loadRecentAttestations() {
                $.ajax({
                    url: "{{ route('attestations.index') }}",
                    method: 'GET',
                    data: { per_page: 10 },
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            renderRecentAttestations(response.data.data);
                        }
                    },
                    error: function(xhr) {
                        $('#recentAttestationsTable').html(
                            '<tr><td colspan="6" class="text-center text-danger">Erreur de chargement</td></tr>'
                        );
                    }
                });
            }

            // Afficher les attestations récentes
            function renderRecentAttestations(attestations) {
                let html = '';

                if (attestations.length === 0) {
                    html = '<tr><td colspan="6" class="text-center text-muted">Aucune attestation</td></tr>';
                } else {
                    attestations.forEach(attestation => {
                        const statusBadge = attestation.status === 'sent'
                            ? '<span class="badge bg-success">Envoyée</span>'
                            : '<span class="badge bg-warning">En attente</span>';

                        html += `
                            <tr>
                                <td><small>${attestation.attestation_number}</small></td>
                                <td>${attestation.participant.name}</td>
                                <td><small>${attestation.periode.libelle}</small></td>
                                <td>${statusBadge}</td>
                                <td><small>${formatDate(attestation.created_at, 'dd/mm/yyyy')}</small></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ url('attestations') }}/${attestation.id}/preview"
                                           class="btn btn-info btn-sm" target="_blank" title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ url('attestations') }}/${attestation.id}/download"
                                           class="btn btn-primary btn-sm" title="Télécharger">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                }

                $('#recentAttestationsTable').html(html);
            }

            // Charger les participants sans attestation
            function loadParticipantsWithoutAttestation() {
                $.ajax({
                    url: "{{ route('participants.without-attestation') }}",
                    method: 'GET',
                    data: { per_page: 10 },
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            renderParticipantsWithoutAttestation(response.data.data);
                            $('#noAttestationCount').text(response.data.total);
                        }
                    },
                    error: function(xhr) {
                        $('#noAttestationTable tbody').html(
                            '<tr><td colspan="2" class="text-center text-danger">Erreur</td></tr>'
                        );
                    }
                });
            }

            // Afficher les participants sans attestation
            function renderParticipantsWithoutAttestation(participants) {
                let html = '';

                if (participants.length === 0) {
                    html = '<tr><td colspan="2" class="text-center text-muted">Tous certifiés !</td></tr>';
                } else {
                    participants.forEach(participant => {
                        html += `
                            <tr>
                                <td>
                                    <small>${participant.name}</small>
                                </td>
                                <td>
                                    <small class="text-muted">${participant.periode.libelle}</small>
                                </td>
                            </tr>
                        `;
                    });
                }

                $('#noAttestationTable tbody').html(html);
            }

            // Rafraîchir toutes les 5 minutes
            setInterval(loadDashboardStats, 300000);
        });
    </script>
@endsection

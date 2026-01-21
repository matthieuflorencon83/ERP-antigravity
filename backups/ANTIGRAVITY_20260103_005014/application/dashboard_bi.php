<?php
/**
 * dashboard_bi.php
 * Module Business Intelligence - Pilotage Financier
 */
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

// Contrôleur BI
require_once 'controllers/bi_controller.php';

$page_title = 'Pilotage & BI';
require_once 'header.php';
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="main-content">
    <div class="container-fluid px-4 mt-4">
        
        <!-- HEADER & FILTRES -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Tableau de Bord Financier</h2>
                <p class="text-muted">Analyse de la rentabilité (Affaires Signées vs Achats Engagés).</p>
            </div>
            <form method="GET" class="d-flex align-items-center bg-white p-2 rounded shadow-sm">
                <label class="me-2 fw-bold text-muted">Exercice :</label>
                <select name="year" class="form-select form-select-sm border-0 bg-light fw-bold" onchange="this.form.submit()">
                    <?php 
                    $current_year = date('Y');
                    for($y = $current_year; $y >= $current_year - 5; $y--): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>

        <!-- KPIS CARDS -->
        <div class="row g-4 mb-4">
            <!-- CA SIGNE -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-uppercase text-muted small fw-bold mb-1">CA Signé (HT)</h6>
                                <h3 class="mb-0 text-primary fw-bold"><?= number_format($ca_annuel, 0, ',', ' ') ?> €</h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-2 rounded">
                                <i class="fas fa-file-invoice text-primary"></i>
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">Basé sur les affaires signées en <?= $year ?></small>
                    </div>
                </div>
            </div>

            <!-- ACHATS ENGAGES -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-uppercase text-muted small fw-bold mb-1">Achats (HT)</h6>
                                <h3 class="mb-0 text-danger fw-bold"><?= number_format($achats_annuel, 0, ',', ' ') ?> €</h3>
                            </div>
                            <div class="bg-danger bg-opacity-10 p-2 rounded">
                                <i class="fas fa-shopping-cart text-danger"></i>
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">Commandes fournisseurs <?= $year ?></small>
                    </div>
                </div>
            </div>

            <!-- MARGE BRUTE -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-uppercase text-muted small fw-bold mb-1">Marge Théorique</h6>
                                <h3 class="mb-0 text-success fw-bold"><?= number_format($marge_brute, 0, ',', ' ') ?> €</h3>
                            </div>
                            <div class="bg-success bg-opacity-10 p-2 rounded">
                                <i class="fas fa-chart-line text-success"></i>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= min($taux_marge, 100) ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAUX MARGE -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <h6 class="text-uppercase text-muted small fw-bold mb-1">Taux de Marge</h6>
                        <h2 class="mb-0 fw-bold <?= $taux_marge > 30 ? 'text-success' : 'text-warning' ?>">
                            <?= number_format($taux_marge, 1, ',', '') ?> %
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABS NAVIGATION -->
        <ul class="nav nav-pills-custom justify-content-center mb-4" id="biTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="perf-tab" data-bs-toggle="pill" data-bs-target="#perf-pane" type="button" role="tab"><i class="fas fa-chart-line me-2"></i>Performance</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="analyse-tab" data-bs-toggle="pill" data-bs-target="#analyse-pane" type="button" role="tab"><i class="fas fa-search-dollar me-2"></i>Analyse Dépenses</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="info-tab" data-bs-toggle="pill" data-bs-target="#info-pane" type="button" role="tab"><i class="fas fa-info-circle me-2"></i>Infos</button>
            </li>
        </ul>

        <!-- TABS CONTENT -->
        <div class="tab-content" id="biTabsContent">
            
            <!-- TAB 1: PERFORMANCE -->
            <div class="tab-pane fade show active" id="perf-pane" role="tabpanel">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2"></i>Évolution Mensuelle (CA vs Dépenses)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="chartEvolution" style="max-height: 50vh;"></canvas>
                    </div>
                </div>
            </div>

            <!-- TAB 2: ANALYSE -->
            <div class="tab-pane fade" id="analyse-pane" role="tabpanel">
                <div class="row g-4">
                    <!-- TOP 10 CLIENTS -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0 fw-bold"><i class="fas fa-users me-2"></i>Top 10 Clients (CA)</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartTopClients" style="max-height: 400px;"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- AFFAIRES PAR STATUT -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2"></i>Affaires par Statut</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartAffairesStatut" style="max-height: 400px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row g-4 mt-1">
                    <!-- TOP 5 FOURNISSEURS -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0 fw-bold"><i class="fas fa-trophy me-2"></i>Top 5 Fournisseurs</h6>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <?php foreach($top_fournisseurs as $index => $tf): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-light text-dark rounded-circle me-3" style="width:25px;height:25px;display:flex;align-items:center;justify-content:center;"><?= $index + 1 ?></span>
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($tf['nom']) ?></span>
                                        </div>
                                        <span class="fw-bold text-secondary"><?= number_format($tf['total_achat'], 0, ',', ' ') ?> €</span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="card-footer bg-white text-center">
                                <a href="commandes_liste.php" class="text-decoration-none small text-muted">Voir toutes les commandes <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CAMEMBERT -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-pie me-2"></i>Répartition des Dépenses</h6>
                            </div>
                            <div class="card-body">
                                <div style="height: 350px; display: flex; justify-content: center;">
                                    <canvas id="chartFamilles"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: INFOS -->
            <div class="tab-pane fade" id="info-pane" role="tabpanel">
                <div class="card border-0 shadow-sm bg-primary text-white" style="background: linear-gradient(45deg, #2c3e50, #34495e);">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-5">
                        <i class="fas fa-lightbulb fa-3x mb-4 text-warning"></i>
                        <h4 class="fw-bold">Le saviez-vous ?</h4>
                        <p class="lead opacity-75">Le taux de marge moyen dans la menuiserie se situe entre 35% et 45%.</p>
                        <?php if($taux_marge < 30): ?>
                            <div class="alert alert-warning text-dark mt-3 mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>Votre marge est inférieure à 30%. Vérifiez vos prix d'achat.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success text-dark mt-3 mb-0">
                                <i class="fas fa-check-circle me-2"></i>Votre performance est excellente !
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div> <!-- End Tab Content -->

    </div>
</div>

<!-- INITIALISATION CHART.JS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // DONNEES PHP -> JS
    const labelsMois = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
    const dataCA = <?= json_encode(array_column($mois_stats, 'ca')) ?>;
    const dataAchat = <?= json_encode(array_column($mois_stats, 'achat')) ?>;

    // 1. Chart Evolution
    const ctxEvol = document.getElementById('chartEvolution').getContext('2d');
    new Chart(ctxEvol, {
        type: 'bar',
        data: {
            labels: labelsMois,
            datasets: [
                {
                    label: 'CA Signé',
                    data: dataCA,
                    backgroundColor: '#0d6efd',
                    borderRadius: 4
                },
                {
                    label: 'Achats',
                    data: dataAchat,
                    backgroundColor: '#dc3545',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: { position: 'bottom' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [2, 2] }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 2. Chart Familles
    <?php
    $fam_labels = [];
    $fam_data = [];
    foreach($repartition_familles as $rf) {
        $fam_labels[] = $rf['nom_famille'] ?: 'Non classé';
        $fam_data[] = $rf['total'];
    }
    ?>
    const ctxFam = document.getElementById('chartFamilles').getContext('2d');
    new Chart(ctxFam, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($fam_labels) ?>,
            datasets: [{
                data: <?= json_encode($fam_data) ?>,
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'
                ],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' }
            }
        }
    });
    
    // 3. Chart Top 10 Clients (Barres horizontales)
    const ctxClients = document.getElementById('chartTopClients').getContext('2d');
    new Chart(ctxClients, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(function($c) { return $c['nom_principal'] . ' ' . ($c['prenom'] ?? ''); }, $top_clients)) ?>,
            datasets: [{
                label: 'CA (€)',
                data: <?= json_encode(array_column($top_clients, 'ca_total')) ?>,
                backgroundColor: '#0d6efd',
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => context.parsed.x.toLocaleString('fr-FR') + ' €'
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => value.toLocaleString('fr-FR') + ' €'
                    }
                }
            }
        }
    });
    
    // 4. Chart Affaires par Statut
    const ctxStatut = document.getElementById('chartAffairesStatut').getContext('2d');
    new Chart(ctxStatut, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($affaires_statut, 'statut')) ?>,
            datasets: [{
                label: 'Nombre',
                data: <?= json_encode(array_column($affaires_statut, 'nombre')) ?>,
                backgroundColor: ['#ffc107', '#198754', '#0d6efd', '#6c757d'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>

<?php require_once 'footer.php'; ?>

<?php
// stocks_liste.php - Inventaire Temps Réel (REFONTE SELECT2)
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';
require_once 'controllers/stocks_controller.php';

$controller = new StocksController($pdo);

// 1. FILTRES & TRI
$search = trim($_GET['q'] ?? '');
$f_famille = trim($_GET['f_famille'] ?? '');
$f_fournisseur = trim($_GET['f_fournisseur'] ?? '');

// Récupération Données
$stocks = $controller->getInventory($search, $f_famille, $f_fournisseur);
$total_value = $controller->getStockValue();
$total_refs = count($stocks);

// Récupération Options Dropdowns
$opt_familles = $controller->getFamilies();
$opt_fournisseurs = $controller->getFournisseurs();

$page_title = 'État des Stocks';
require_once 'header.php';
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .select2-container--bootstrap-5 .select2-selection { border-color: #dee2e6; }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
    .select2-container .select2-selection--single { height: 31px !important; }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height: 29px !important; font-size: 0.875rem; }
    
    /* Responsive Ajustement */
    @media (max-width: 768px) {
        .kpi-row { display: flex; overflow-x: auto; gap: 1rem; padding-bottom: 0.5rem; }
        .kpi-card { min-width: 250px; }
    }
</style>

<div class="main-content">
    <div class="container-fluid mt-3 px-2 px-md-4">
        
        <!-- HEADER & ACTIONS -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="fw-bold text-petrol mb-0"><i class="fas fa-boxes me-2"></i>Inventaire Atelier</h2>
                <p class="text-muted mb-0 small">Vue en temps réel des stocks physiques.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="stocks_mouvements.php" class="btn btn-outline-secondary rounded-pill shadow-sm">
                    <i class="fas fa-history me-2"></i>Historique
                </a>
                <a href="stocks_sortie.php" class="btn btn-danger text-white rounded-pill px-4 shadow-sm">
                    <i class="fas fa-sign-out-alt me-2"></i>Nouvelle Sortie
                </a>
            </div>
        </div>

        <!-- KPI CARDS -->
        <div class="row mb-4 kpi-row">
            <div class="col-md-3 kpi-card">
                <div class="card shadow-sm border-0 border-start border-4 border-primary h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-bold">Références en Stock</div>
                        <div class="fs-2 fw-bold text-dark"><?= $total_refs ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 kpi-card">
                <div class="card shadow-sm border-0 border-start border-4 border-success h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-bold">Valorisation (Estimée)</div>
                        <div class="fs-2 fw-bold text-success"><?= number_format($total_value, 2, ',', ' ') ?> €</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEARCH BAR (Global) -->
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body p-3">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md flex-grow-1">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control border-0 bg-light" placeholder="Chercher une référence, désignation..." value="<?= h($search) ?>">
                        </div>
                    </div>
                    <div class="col-6 col-md-auto d-grid">
                        <button type="submit" class="btn btn-secondary shadow-sm">Filtrer</button>
                    </div>
                    <?php if($search || $f_famille || $f_fournisseur): ?>
                        <div class="col-6 col-md-auto d-grid">
                            <a href="stocks_liste.php" class="btn btn-outline-danger shadow-sm"><i class="fas fa-times"></i></a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- DATAGRID -->
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-ag-theme table-mobile-cards">
                    <thead>
                        <tr>
                            <th>Article / Désignation</th>
                            <th>Référence</th>
                            <th>Famille</th>
                            <th>Fournisseur</th>
                            <th class="text-center">Quantité</th>
                            <th class="text-end">Valeur Totale</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        <!-- FILTRES SELECT2 (Desktop) -->
                        <tr class="d-none d-md-table-row bg-light">
                            <th class="p-1"></th> <!-- Article -->
                            <th class="p-1"></th> <!-- Reference -->
                            <th class="p-1" style="min-width: 150px;">
                                <select class="form-select form-select-sm select2-filter" name="f_famille" data-placeholder="Famille...">
                                    <option value=""></option>
                                    <?php foreach($opt_familles as $fam): ?>
                                        <option value="<?= h($fam) ?>" <?= $f_famille === $fam ? 'selected' : '' ?>><?= h($fam) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </th>
                            <th class="p-1" style="min-width: 150px;">
                                <select class="form-select form-select-sm select2-filter" name="f_fournisseur" data-placeholder="Fournisseur...">
                                    <option value=""></option>
                                    <?php foreach($opt_fournisseurs as $frn): ?>
                                        <option value="<?= h($frn) ?>" <?= $f_fournisseur === $frn ? 'selected' : '' ?>><?= h($frn) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </th>
                            <th class="p-1"></th> <!-- Quantité -->
                            <th class="p-1"></th> <!-- Valeur -->
                            <th class="p-1"></th> <!-- Actions -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stocks)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">Aucun article trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach ($stocks as $art): ?>
                                <tr>
                                    <td data-label="Article">
                                        <div class="fw-bold text-dark"><?= h($art['designation']) ?></div>
                                        <?php if($art['nom_couleur']): ?>
                                            <small class="text-muted"><i class="fas fa-palette me-1"></i><?= h($art['nom_couleur']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Référence">
                                        <span class="text-secondary small font-monospace"><?= h($art['ref_fournisseur']) ?></span>
                                    </td>
                                    <td data-label="Famille">
                                        <span class="badge bg-light text-dark border"><?= h($art['famille'] ?: 'Divers') ?></span>
                                    </td>
                                    <td data-label="Fournisseur">
                                        <?= h($art['nom_fournisseur'] ?: '-') ?>
                                    </td>
                                    <td data-label="Quantité" class="text-center">
                                        <div class="d-flex flex-column align-items-center">
                                             <span class="badge rounded-pill <?= $art['quantite'] <= ($art['stock_minimum'] ?? 0) ? 'bg-danger' : 'bg-success' ?> fs-6">
                                                <?= $art['quantite'] + 0 ?>
                                            </span>
                                            <?php if($art['quantite'] <= ($art['stock_minimum'] ?? 0)): ?>
                                                <small class="text-danger fw-bold mt-1" style="font-size: 0.7rem;">STOCK CRITIQUE</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td data-label="Valeur Totale" class="text-end fw-bold text-petrol">
                                        <!-- Calcul approximatif basé sur prix achat actuel * qté -->
                                         - 
                                    </td>
                                    <td data-label="Actions" class="text-end">
                                        <!-- Actions futures (Edit, Mvt Manuel) -->
                                        <button class="btn btn-sm btn-outline-secondary rounded-circle"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2-filter').select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true,
        placeholder: function() {
            return $(this).data('placeholder');
        }
    });

    $('.select2-filter').on('change', function() {
        var key = $(this).attr('name');
        var value = $(this).val();
        filterList(key, value);
    });
});

function filterList(key, value) {
    const url = new URL(window.location.href);
    if(value) {
        url.searchParams.set(key, value);
    } else {
        url.searchParams.delete(key);
    }
    // On garde 'q' si existe
    window.location.href = url.toString();
}
</script>

<?php require_once 'footer.php'; ?>

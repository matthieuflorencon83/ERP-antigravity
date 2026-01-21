<?php
/**
 * stocks_cockpit.php
 * Interface unifiée de gestion de stock "Apex Omniscience".
 */
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';
require_once 'controllers/stocks_controller.php';

$controller = new StocksController($pdo);

// Filtres Initiaux
// Filtres Initiaux
$search = trim($_GET['q'] ?? '');
$f_famille = trim($_GET['f_famille'] ?? '');
$f_fournisseur = trim($_GET['f_fournisseur'] ?? '');

// Options pour filtres
$opt_familles = $controller->getFamilies();
$opt_fournisseurs = $controller->getFournisseurs();

$stocks = $controller->getInventory($search, $f_famille, $f_fournisseur);

$page_title = 'Cockpit Logistique';
$page_title = 'Cockpit Logistique';
require_once 'header.php';
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .select2-container--bootstrap-5 .select2-selection { border-color: #dee2e6; font-size: 0.875rem; }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
</style>

<!-- CSS Spécifique Cockpit -->
<style>
    /* Layout Split */
    .cockpit-container {
        display: flex;
        height: calc(100vh - 80px); /* Full height minus header */
        overflow: hidden;
    }
    .panel-list {
        flex: 1;
        overflow-y: auto;
        border-right: 1px solid #dee2e6;
        padding: 1rem;
        background: #f8f9fa;
    }
    .panel-details {
        width: 400px; /* Fixed width sidebar */
        background: #fff;
        overflow-y: auto;
        box-shadow: -5px 0 15px rgba(0,0,0,0.05);
        z-index: 100;
        transition: transform 0.3s ease;
    }

    /* Dark Mode Overrides */
    [data-bs-theme="dark"] .panel-list {
        background: #212529 !important;
        border-right-color: #495057 !important;
    }
    [data-bs-theme="dark"] .panel-details {
        background: #2b3035 !important;
        box-shadow: -5px 0 15px rgba(0,0,0,0.5);
    }
    [data-bs-theme="dark"] .select2-selection {
        background-color: #2b3035 !important;
        border-color: #495057 !important;
        color: #fff !important;
    }
    [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection__rendered {
        color: #fff !important;
    }
    [data-bs-theme="dark"] .select2-dropdown {
        background-color: #2b3035 !important;
        border-color: #495057 !important;
    }
    [data-bs-theme="dark"] .select2-results__option {
        color: #fff !important;
    }
    [data-bs-theme="dark"] .select2-results__option--highlighted {
        background-color: #0d6efd !important;
    }

    .panel-details.d-none {
        display: none !important;
    }
    
    /* Table Rows */
    .stock-row { cursor: pointer; transition: background 0.1s; }
    .stock-row:hover { background-color: #e9ecef; }
    .stock-row.table-active { background-color: #cfe2ff !important; border-left: 4px solid #0d6efd; }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .cockpit-container { position: relative; }
        .panel-details {
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            width: 100%;
        }
    }
</style>

<div class="cockpit-container">
    
    <!-- GAUCHE : LA MATRICE (Liste) -->
    <div class="panel-list" id="list-panel">
        
        <!-- Header / Filtres -->
        <div class="d-flex justify-content-between align-items-center mb-3 p-3 rounded shadow-sm" style="background: linear-gradient(135deg, #0f4c75 0%, #3282b8 100%); color: white;">
            <div class="d-flex align-items-center gap-3">
                <h4 class="fw-bold m-0"><i class="fas fa-boxes me-2"></i>Stock Atelier</h4>
                <div class="btn-group">
                    <a href="stocks_mouvements.php" class="btn btn-sm btn-outline-light px-3" title="Voir l'historique complet">
                        <i class="fas fa-history me-2"></i>Historique
                    </a>
                    <button class="btn btn-sm btn-outline-warning text-white px-3" id="btn-inventory-mode" title="Lancer un inventaire">
                         <i class="fas fa-clipboard-check me-2"></i>Inventaire
                    </button>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                
                <!-- Filter: Famille -->
                <div style="width: 200px;">
                    <select class="form-select form-select-sm select2-filter" name="f_famille" data-placeholder="Famille...">
                        <option value=""></option>
                        <?php foreach($opt_familles as $fam): ?>
                            <option value="<?= h($fam) ?>" <?= $f_famille === $fam ? 'selected' : '' ?>><?= h($fam) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filter: Fournisseur -->
                <div style="width: 200px;">
                    <select class="form-select form-select-sm select2-filter" name="f_fournisseur" data-placeholder="Fournisseur...">
                        <option value=""></option>
                        <?php foreach($opt_fournisseurs as $frn): ?>
                            <option value="<?= h($frn) ?>" <?= $f_fournisseur === $frn ? 'selected' : '' ?>><?= h($frn) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <input type="text" class="form-control form-control-sm" style="width: 200px;" placeholder="Recherche..." value="<?= h($search) ?>" id="search-input">
                <button class="btn btn-sm btn-light text-primary" onclick="applyFilters()"><i class="fas fa-search"></i></button>
                <?php if($search || $f_famille || $f_fournisseur): ?>
                     <a href="stocks_cockpit.php" class="btn btn-sm btn-outline-light" title="Reset"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Grille Données -->
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Article</th>
                            <th>Fournisseur</th>
                            <th>Référence</th>
                            <th class="text-center">Stock</th>
                            <th>Emplacement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stocks as $art): ?>
                            <tr class="stock-row" data-id="<?= $art['article_id'] ?>">
                                <td>
                                    <div class="fw-bold"><?= h($art['designation']) ?></div>
                                    <small class="text-muted"><?= h($art['famille']) ?></small>
                                </td>
                                <td>
                                    <small class="text-secondary"><?= h($art['nom_fournisseur'] ?: '-') ?></small>
                                </td>
                                <td class="font-monospace text-secondary"><?= h($art['ref_fournisseur']) ?></td>
                                <td class="text-center">
                                    <span class="badge rounded-pill <?= $art['quantite'] <= 0 ? 'bg-danger' : 'bg-success' ?>">
                                        <?= $art['quantite'] + 0 ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-success btn-quick-mvt" data-type="ENTREE" title="Entrée Rapide"><i class="fas fa-plus"></i></button>
                                        <button class="btn btn-outline-danger btn-quick-mvt" data-type="SORTIE" title="Sortie Rapide"><i class="fas fa-minus"></i></button>
                                    </div>
                                    <small class="ms-2"><i class="fas fa-warehouse text-muted me-1"></i>Atelier</small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($stocks)): ?>
                            <tr><td colspan="4" class="text-center p-4 text-muted">Aucun article.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- DROITE : LE TACTIQUE (Panneau Détails) -->
    <div class="panel-details d-none" id="details-panel">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light sticky-top">
            <h6 class="m-0 fw-bold">Détails Article</h6>
            <button class="btn-close" id="close-panel"></button>
        </div>
        <div id="panel-content">
            <!-- Chargé via AJAX -->
        </div>
    </div>

</div>

<!-- Module JS -->
<script type="module" src="assets/js/modules/stock_cockpit.js?v=<?= time() ?>"></script>

<?php require_once 'footer.php'; ?>

<!-- Select2 JS & Logic -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2-filter').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            placeholder: function() { return $(this).data('placeholder'); }
        });

        // Trigger search on Enter in input
        $('#search-input').on('keypress', function(e) {
            if(e.which == 13) applyFilters();
        });
    });

    function applyFilters() {
        const params = new URLSearchParams(window.location.search);
        params.set('q', $('#search-input').val());
        params.set('f_famille', $('select[name="f_famille"]').val());
        params.set('f_fournisseur', $('select[name="f_fournisseur"]').val());
        window.location.href = '?' + params.toString();
    }
</script>

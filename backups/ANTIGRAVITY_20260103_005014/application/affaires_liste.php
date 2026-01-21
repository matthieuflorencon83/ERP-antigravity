<?php
// affaires_liste.php - Liste complète des dossiers (REFONTE MOBILE 2025)
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

// 1. GESTION DE LA RECHERCHE & TRI
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'id'; // Colonne de tri
$order = $_GET['order'] ?? 'DESC'; // Ordre (ASC/DESC)

// Whitelist des colonnes triables
$valid_sorts = ['numero_prodevis', 'nom_principal', 'nom_affaire', 'statut', 'date_creation'];
if (!in_array($sort, $valid_sorts)) $sort = 'id';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Récupération des listes pour les filtres (Dropdowns comme avant)
try {
    $opt_clients = $pdo->query("SELECT DISTINCT c.nom_principal FROM clients c JOIN affaires a ON a.client_id = c.id ORDER BY c.nom_principal")->fetchAll(PDO::FETCH_COLUMN);
    $opt_chantiers = $pdo->query("SELECT DISTINCT nom_affaire FROM affaires ORDER BY nom_affaire")->fetchAll(PDO::FETCH_COLUMN);
    $opt_refs = $pdo->query("SELECT DISTINCT numero_prodevis FROM affaires ORDER BY numero_prodevis DESC")->fetchAll(PDO::FETCH_COLUMN);
    // Fetch distinct Month/Year for Date Filter
    $opt_dates = $pdo->query("SELECT DISTINCT DATE_FORMAT(date_creation, '%Y-%m') as ym FROM affaires ORDER BY ym DESC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $opt_clients = []; $opt_chantiers = []; $opt_refs = []; $opt_dates = [];
}

// Fonction pour générer les liens de tri inverse
function sort_link($field, $label, $current_sort, $current_order) {
    $new_order = ($current_sort === $field && $current_order === 'DESC') ? 'ASC' : 'DESC';
    $icon = '';
    if ($current_sort === $field) {
        $icon = ($current_order === 'DESC') ? ' <i class="fas fa-sort-down"></i>' : ' <i class="fas fa-sort-up"></i>';
    }
    return "<a href='?sort=$field&order=$new_order&q=" . h($_GET['q']??'') . "' class='text-white text-decoration-none'>$label$icon</a>";
}

// Filtres Colonnes
$f_ref = trim($_GET['f_ref'] ?? '');
$f_client = trim($_GET['f_client'] ?? '');
$f_chantier = trim($_GET['f_chantier'] ?? '');
$f_statut = trim($_GET['f_statut'] ?? '');
$f_date = trim($_GET['f_date'] ?? '');

$params = [];
$sql = "SELECT a.*, c.nom_principal,
        (SELECT COUNT(*) FROM commandes_achats ca WHERE ca.affaire_id = a.id) as nb_commandes
        FROM affaires a 
        JOIN clients c ON a.client_id = c.id 
        WHERE 1=1";

// Global Search
if ($search) {
    $sql .= " AND (c.nom_principal LIKE ? OR a.nom_affaire LIKE ? OR a.numero_prodevis LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Column Filters
if ($f_ref) { $sql .= " AND a.numero_prodevis LIKE ?"; $params[] = "%$f_ref%"; }
if ($f_client) { $sql .= " AND c.nom_principal LIKE ?"; $params[] = "%$f_client%"; }
if ($f_chantier) { $sql .= " AND a.nom_affaire LIKE ?"; $params[] = "%$f_chantier%"; }
if ($f_statut) { $sql .= " AND a.statut LIKE ?"; $params[] = "%$f_statut%"; }
if ($f_date) { $sql .= " AND DATE_FORMAT(a.date_creation, '%Y-%m') = ?"; $params[] = $f_date; }

$sql .= " ORDER BY " . ($sort == 'nom_principal' ? 'c.nom_principal' : "a.$sort") . " $order LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$affaires = $stmt->fetchAll();

// Récupération Détails Commandes (Optimisé)
foreach ($affaires as &$aff) {
    if ($aff['nb_commandes'] > 0) {
        $stmt = $pdo->prepare("
            SELECT ca.statut, ca.ref_interne, ca.date_commande, f.nom as nom_fournisseur 
            FROM commandes_achats ca 
            LEFT JOIN fournisseurs f ON ca.fournisseur_id = f.id 
            WHERE ca.affaire_id = ? 
            ORDER BY ca.date_commande DESC
        ");
        $stmt->execute([$aff['id']]);
        $aff['commandes_details'] = $stmt->fetchAll();
    } else {
        $aff['commandes_details'] = [];
    }
}
unset($aff);

$page_title = 'Mes Affaires';
require_once 'header.php';
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    /* Custom style to match screenshot */
    .select2-container--bootstrap-5 .select2-selection {
        border-color: #dee2e6;
    }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    /* SPLIT TABLE LAYOUT */
    html, body { height: 100%; overflow: hidden; }
    
    /* Target the container opened in header.php */
    .ag-main-content {
        height: calc(100vh - 110px); /* Adjust for Header + Ticker + Padding */
        overflow: hidden;
        display: flex;
        flex-direction: column;
        padding-bottom: 0 !important;
    }

    /* Target the wrapper in affaires_liste.php */
    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        min-height: 0;
    }

    /* Target the bootstrap container inside main-content */
    .main-content > .container-fluid {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
        padding-bottom: 1.5rem !important; /* Bottom spacing */
    }
    
    /* CONTAINER PRINCIPAL */
    .table-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden; /* No global scroll */
        min-height: 0;
        
        /* Simulates card appearance BUT transparent for dark mode compat */
        background-color: transparent; 
        border: 0;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    }
    
    /* HEADER WRAPPER (Fixed) */
    .header-wrapper {
        flex: 0 0 auto;
        /* padding-right value handled by JS for perfect sync */
        padding-right: 0; 
        background: var(--bs-tertiary-bg); /* Adaptive */
        border-bottom: 1px solid var(--bs-border-color);
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
        overflow: hidden; /* No scroll */
    }

    /* BODY WRAPPER (Scrollable) */
    .body-wrapper {
        flex: 1;
        overflow-y: auto; /* Scroll bar here */
        border-bottom-left-radius: 0.5rem;
        border-bottom-right-radius: 0.5rem;
        /* Background transparent to show page bg in dark mode */
        background: transparent; 
    }

    /* SHARED TABLE STYLES */
    .table-split {
        width: 100%;
        table-layout: fixed; /* CRITICAL FOR ALIGNMENT */
        margin-bottom: 0;
    }
    
    .table-split th, .table-split td {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Specific Header Styles */
    .header-wrapper th {
        border-bottom: none !important;
        background-color: var(--bs-tertiary-bg);
        color: var(--bs-body-color);
        padding-top: 1rem;
        padding-bottom: 1rem;
        height: 50px;
        vertical-align: middle;
    }
    
    /* Specific Body Styles */
    .body-wrapper td {
        border-top: 1px solid var(--bs-border-color);
        padding: 0.75rem 0.5rem;
    }

    /* Dropdown alignment constraint */
    .form-select-sm {
        min-width: 0; /* Allow shrink */
    }
    .select2-container { width: 100% !important; }
    
    /* Rows clickable */
    tbody tr { cursor: pointer; transition: background-color 0.15s; }
    tbody tr:hover { background-color: rgba(0,0,0,0.03) !important; }
</style>

<div class="main-content">
    <div class="container-fluid px-2 px-md-4 mt-3">
        
        <!-- FLASHLIGHTS -->
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- ACTIONS (Titre géré par le Header Global) -->
        <div class="d-flex justify-content-end align-items-center mb-4 gap-2">
            <a href="affaires_nouveau.php" class="btn btn-petrol rounded-pill shadow-sm">
                <i class="fas fa-plus-circle me-2"></i>Nouvelle Affaire
            </a>
            <a href="commandes_saisie.php" class="btn btn-accent rounded-pill shadow-sm">
                <i class="fas fa-shopping-cart me-2"></i>Nouvelle Commande
            </a>
        </div>

        <!-- SEARCH BAR -->
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body p-3">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md flex-grow-1">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control border-0 bg-light" placeholder="Chercher un client, N° devis, chantier..." value="<?= h($search) ?>">
                        </div>
                    </div>
                    <div class="col-6 col-md-auto d-grid">
                        <button type="submit" class="btn btn-secondary shadow-sm">Filtrer</button>
                    </div>
                    <?php if($search): ?>
                    <div class="col-6 col-md-auto d-grid">
                         <a href="affaires_liste.php" class="btn btn-outline-danger shadow-sm"><i class="fas fa-times"></i></a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- TABLE AREA SPLIT -->
        <div class="table-container">
            
            <?php
            // COLGROUP SHARED
            $colgroup = '
            <colgroup>
                <col style="width: 10%;"> <!-- Date -->
                <col style="width: 10%;"> <!-- ProDevis -->
                <col style="width: 15%;"> <!-- Client -->
                <col style="width: 15%;"> <!-- Chantier -->
                <col style="width: 23%;"> <!-- Designation (Reduced 25->23) -->
                <col style="width: 12%;"> <!-- Commandes -->
                <col style="width: 6%;">  <!-- Statut (Reduced 7->6) -->
                <col style="width: 9%;">  <!-- Actions (Increased 6->9) -->
            </colgroup>';
            ?>

            <!-- 1. HEADER FIXED -->
            <div class="header-wrapper shadow-sm">
                <table class="table mb-0 table-split">
                    <?= $colgroup ?>
                    <thead>
                        <tr>
                            <th class="text-center"><?= sort_link('date_creation', 'Créée le', $sort, $order) ?></th>
                            <th><?= sort_link('numero_prodevis', 'N° ProDevis', $sort, $order) ?></th>
                            <th><?= sort_link('nom_principal', 'Client', $sort, $order) ?></th>
                            <th><?= sort_link('nom_affaire', 'Chantier', $sort, $order) ?></th>
                            <th>Désignation</th>
                            <th class="text-center">Commandes</th>
                            <th class="text-center"><?= sort_link('statut', 'Statut', $sort, $order) ?></th>
                            <th class="text-end">Actions</th>
                        </tr>
                        <!-- Ligne Filtrage (Desktop Only) -->
                        <tr class="d-none d-md-table-row bg-light">
                            <th class="p-1">
                                <select class="form-select form-select-sm select2-filter" name="f_date" data-placeholder="Mois/Année">
                                    <option value=""></option>
                                    <?php foreach($opt_dates as $ym): ?>
                                        <option value="<?= h($ym) ?>" <?= $f_date == $ym ? 'selected' : '' ?>><?= h($ym) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </th>
                            <th class="p-1">
                                <select class="form-select form-select-sm select2-filter" name="f_ref" data-placeholder="Filtrer Réf">
                                    <option value=""></option>
                                    <?php foreach($opt_refs as $r): ?>
                                        <option value="<?= h($r) ?>" <?= $f_ref == $r ? 'selected' : '' ?>><?= h($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </th>
                            <th class="p-1">
                                <select class="form-select form-select-sm select2-filter" name="f_client" data-placeholder="Filtrer Client">
                                    <option value=""></option>
                                    <?php foreach($opt_clients as $nom): ?>
                                        <option value="<?= h($nom) ?>" <?= $f_client == $nom ? 'selected' : '' ?>><?= h($nom) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </th>
                            <th class="p-1">
                                <select class="form-select form-select-sm select2-filter" name="f_chantier" data-placeholder="Filtrer Chantier">
                                    <option value=""></option>
                                    <?php foreach($opt_chantiers as $nom): ?>
                                        <option value="<?= h($nom) ?>" <?= $f_chantier == $nom ? 'selected' : '' ?>><?= h($nom) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </th>
                            <th class="p-1"></th> <!-- Désignation (Large) -->
                            <th class="p-1"></th> <!-- Commandes -->
                            <th class="p-1">
                                <select class="form-select form-select-sm" onchange="filterList('f_statut', this.value)">
                                    <option value="">Tous</option>
                                    <option value="Devis" <?= $f_statut == 'Devis' ? 'selected' : '' ?>>Devis</option>
                                    <option value="Signé" <?= $f_statut == 'Signé' ? 'selected' : '' ?>>Signé</option>
                                    <option value="Clôturé" <?= $f_statut == 'Clôturé' ? 'selected' : '' ?>>Clôturé</option>
                                </select>
                            </th>
                            <th class="p-1"></th> <!-- Actions -->
                        </tr>
                    </thead>
                </table>
            </div>

            <!-- 2. BODY SCROLLABLE -->
            <div class="body-wrapper shadow-sm">
                <table class="table table-hover align-middle mb-0 table-split">
                    <?= $colgroup ?>
                    <tbody>
                        <?php if(empty($affaires)): ?>
                            <tr><td colspan="8" class="text-center py-5 text-muted">Aucune affaire trouvée.</td></tr>
                        <?php else: ?>
                            <?php foreach($affaires as $aff): ?>
                            <tr onclick="window.location.href='affaires_detail.php?id=<?= $aff['id'] ?>'">
                                <td class="text-center text-muted small">
                                    <?= date('d/m/Y', strtotime($aff['date_creation'])) ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-petrol">#<?= h($aff['numero_prodevis']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark text-truncate"><?= h($aff['nom_principal']) ?></div>
                                </td>
                                <td>
                                    <div class="text-primary fw-medium text-truncate"><?= h($aff['nom_affaire']) ?></div>
                                </td>
                                <td>
                                    <small class="text-muted text-truncate d-block">
                                        <?= h($aff['designation'] ?? '-') ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <?php if ($aff['nb_commandes'] > 0): ?>
                                        <div class="d-flex align-items-center justify-content-center gap-1 flex-wrap">
                                            <span class="badge bg-secondary rounded-pill me-1"><?= $aff['nb_commandes'] ?></span>
                                            <?php foreach ($aff['commandes_details'] as $cmd): ?>
                                                <?php
                                                $st = mb_strtolower(trim($cmd['statut'] ?? ''), 'UTF-8');
                                                $color = match(true) {
                                                    str_contains($st, 'brouillon') => 'secondary',
                                                    str_contains($st, 'envoy') || str_contains($st, 'command') => 'primary',
                                                    str_contains($st, 'confirm') || str_contains($st, 'arc') => 'warning',
                                                    str_contains($st, 'reçu') || str_contains($st, 'recu') || str_contains($st, 'livr') => 'success',
                                                    str_contains($st, 'annul') => 'danger',
                                                    default => 'secondary opacity-25'
                                                };
                                                $tooltip = "<strong>Ref:</strong> " . h($cmd['ref_interne']) . "<br>";
                                                $tooltip .= "<strong>Frn:</strong> " . h($cmd['nom_fournisseur']) . "<br>";
                                                $date_aff = !empty($cmd['date_commande']) ? date('d/m/Y', strtotime($cmd['date_commande'])) : '-';
                                                $tooltip .= "<strong>Date:</strong> " . $date_aff . "<br>";
                                                $tooltip .= "<strong>Statut:</strong> " . h($cmd['statut']);
                                                ?>
                                                <i class="fas fa-circle text-<?= $color ?> shadow-sm" 
                                                   style="font-size: 0.6rem; text-shadow: 0 0 2px currentColor;" 
                                                   data-bs-toggle="tooltip" 
                                                   data-bs-html="true" 
                                                   title="<?= $tooltip ?>"></i>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?= badge_statut($aff['statut']) ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="affaires_modifier.php?id=<?= $aff['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Modifier" onclick="event.stopPropagation();">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="affaires_actions.php?action=delete&id=<?= $aff['id'] ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="event.stopPropagation(); return confirm('Êtes-vous sûr de vouloir supprimer cette affaire ?\n(Impossible si des commandes sont liées)')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
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

<?php require_once 'footer.php'; ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Init Select2
        $('.select2-filter').select2({
            theme: "bootstrap-5",
            allowClear: true,
            width: '100%' // Important pour l'alignement dans le TH
        });

        // Submit form on Select2 change
        $('.select2-filter').on('change', function() {
            $(this).closest('form').submit();
        });

        // Init Bootstrap Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });

    function filterList(param, value) {
        let url = new URL(window.location.href);
        url.searchParams.set(param, value);
        window.location.href = url.toString();
    }
</script>
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

    // Trigger filter when value changes
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
        window.location.href = url.toString();
    }
    
    // SYNC SCROLL (Horizontal)
    $('.body-wrapper').on('scroll', function() { 
        $('.header-wrapper').scrollLeft($(this).scrollLeft()); 
    });

    // ADJUST SCROLLBAR PADDING (Dynamic)
    function adjustHeaderPadding() {
        var $body = $('.body-wrapper');
        var $header = $('.header-wrapper');
        // Calculate width of scrollbar
        var scrollbarWidth = $body[0].offsetWidth - $body[0].clientWidth;
        // Apply to Header Wrapper
        $header.css('padding-right', scrollbarWidth + 'px');
    }

    // Call on load and resize
    $(window).on('resize', adjustHeaderPadding);
    adjustHeaderPadding();
</script>
<?php require_once 'footer.php'; ?>

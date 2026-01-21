<?php
// commandes_liste.php - Liste des commandes fournisseurs (REFONTE MOBILE + FILTRES SELECT2)
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

// 1. GESTION DU TRI & FILTRES
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'date_cmd'; // Default sort changed to match first column logic if needed, but date_commande is fine
$order = $_GET['order'] ?? 'DESC';

// Whitelist tri
$valid_sorts = ['ref_interne', 'date_commande', 'fournisseur_nom', 'nom_affaire', 'total_ht', 'statut'];
if (!in_array($sort, $valid_sorts)) $sort = 'date_commande';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Filtres Colonnes
$f_date = trim($_GET['f_date'] ?? '');
$f_ref = trim($_GET['f_ref'] ?? '');
$f_fournisseur = trim($_GET['f_fournisseur'] ?? '');
$f_affaire = trim($_GET['f_affaire'] ?? '');
$f_statut = trim($_GET['f_statut'] ?? '');

function sort_link($field, $label, $current_sort, $current_order) {
    global $search, $f_date, $f_ref, $f_fournisseur, $f_affaire, $f_statut;
    $new_order = ($current_sort === $field && $current_order === 'DESC') ? 'ASC' : 'DESC';
    $icon = '';
    if ($current_sort === $field) {
        $icon = ($current_order === 'DESC') ? ' <i class="fas fa-sort-down"></i>' : ' <i class="fas fa-sort-up"></i>';
    }
    // Reconstruire l'URL avec tous les params
    $query = http_build_query(array_merge($_GET, ['sort' => $field, 'order' => $new_order]));
    return "<a href='?$query' class='text-decoration-none text-dark fw-bold'>$label$icon</a>";
}

// 2. RÉCUPÉRATION DES DONNÉES (FILTRES DROPDOWNS)
try {
    $opt_fournisseurs = $pdo->query("SELECT DISTINCT f.nom FROM fournisseurs f JOIN commandes_achats ca ON ca.fournisseur_id = f.id ORDER BY f.nom")->fetchAll(PDO::FETCH_COLUMN);
    $opt_affaires = $pdo->query("SELECT DISTINCT a.nom_affaire FROM affaires a JOIN commandes_achats ca ON ca.affaire_id = a.id ORDER BY a.nom_affaire")->fetchAll(PDO::FETCH_COLUMN);
    $opt_refs = $pdo->query("SELECT DISTINCT ref_interne FROM commandes_achats ORDER BY ref_interne DESC")->fetchAll(PDO::FETCH_COLUMN);
    // Dates : Mois/Année
    $opt_dates = $pdo->query("SELECT DISTINCT DATE_FORMAT(date_commande, '%Y-%m') as ym FROM commandes_achats WHERE date_commande IS NOT NULL ORDER BY ym DESC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $opt_fournisseurs = []; $opt_affaires = []; $opt_refs = []; $opt_dates = [];
}

// 3. REQUÊTE PRINCIPALE (UNION STANDARD + EXPRESS)
$params = [];

// Base Query Parts
$sql = "SELECT * FROM (
            SELECT 
                ca.id as original_id,
                ca.ref_interne as ref_display,
                ca.date_commande as date_cmd,
                f.nom as fournisseur_nom,
                a.nom_affaire,
                a.numero_prodevis,
                (SELECT SUM(la.prix_unitaire_achat * la.qte_commandee) FROM lignes_achat la WHERE la.commande_id = ca.id) as total_ht,
                ca.statut,
                'STANDARD' as source_type,
                ca.designation as designation,
                NULL as module_type,
                ca.date_commande,
                ca.date_arc_recu,
                ca.date_livraison_prevue,
                ca.date_livraison_reelle,
                ca.date_en_attente,
                NULL as created_at
            FROM commandes_achats ca
            LEFT JOIN fournisseurs f ON ca.fournisseur_id = f.id
            LEFT JOIN affaires a ON ca.affaire_id = a.id
            
            UNION ALL
            
            SELECT 
                ce.id as original_id,
                CONCAT('EXP-', ce.id) as ref_display,
                ce.created_at as date_cmd,
                ce.fournisseur_nom,
                a.nom_affaire,
                a.numero_prodevis,
                0 as total_ht,
                ce.statut,
                'EXPRESS' as source_type,
                CONCAT('Commande Rapide: ', ce.module_type) as designation,
                ce.module_type,
                NULL as date_commande,
                NULL as date_arc_recu,
                NULL as date_livraison_prevue,
                NULL as date_livraison_reelle,
                NULL as date_en_attente,
                ce.created_at
            FROM commandes_express ce
            LEFT JOIN affaires a ON ce.affaire_id = a.id
        ) as combined_orders
        WHERE 1=1";

// Filtre Texte
if ($search) {
    $sql .= " AND (ref_display LIKE ? OR fournisseur_nom LIKE ? OR nom_affaire LIKE ? OR designation LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Application des Filtres Colonnes
if ($f_ref) { $sql .= " AND ref_display LIKE ?"; $params[] = "%$f_ref%"; }
if ($f_fournisseur) { $sql .= " AND fournisseur_nom LIKE ?"; $params[] = "%$f_fournisseur%"; }
if ($f_affaire) { $sql .= " AND nom_affaire LIKE ?"; $params[] = "%$f_affaire%"; }

// Filtre Statut (basé sur les dates)
if ($f_statut === 'En Attente') {
    $sql .= " AND (date_commande IS NULL OR (source_type = 'EXPRESS'))";
} elseif ($f_statut === 'Commandée') {
    $sql .= " AND date_commande IS NOT NULL AND date_arc_recu IS NULL AND source_type = 'STANDARD'";
} elseif ($f_statut === 'ARC Reçu') {
    $sql .= " AND date_arc_recu IS NOT NULL AND date_livraison_prevue IS NULL AND source_type = 'STANDARD'";
} elseif ($f_statut === 'Livraison Prévue') {
    $sql .= " AND date_livraison_prevue IS NOT NULL AND date_livraison_reelle IS NULL AND source_type = 'STANDARD'";
} elseif ($f_statut === 'Livrée') {
    $sql .= " AND date_livraison_reelle IS NOT NULL AND source_type = 'STANDARD'";
}

if ($f_date) { 
    // Filtre sur YYYY-MM
    $sql .= " AND date_cmd LIKE ?"; 
    $params[] = "$f_date%"; 
}

// NEW: Filtre Stage (Cycle de vie) - Synchronisé avec Dashboard
$f_stage = $_GET['f_stage'] ?? '';
if ($f_stage === 'draft') {
    // En Attente
    $sql .= " AND date_cmd IS NULL AND source_type = 'STANDARD'"; 
} elseif ($f_stage === 'ordered') {
    // Commandées (mais pas ARC)
    $sql .= " AND date_cmd IS NOT NULL 
              AND source_type = 'STANDARD' 
              AND original_id IN (SELECT id FROM commandes_achats WHERE date_arc_recu IS NULL)";
} elseif ($f_stage === 'arc') {
    // ARC Reçus (mais pas livrés)
    $sql .= " AND source_type = 'STANDARD' 
              AND original_id IN (SELECT id FROM commandes_achats WHERE date_arc_recu IS NOT NULL AND date_livraison_reelle IS NULL)";
} elseif ($f_stage === 'delivery') {
    // Livraisons Prévues (mais pas livrées)
    $sql .= " AND source_type = 'STANDARD' 
              AND original_id IN (SELECT id FROM commandes_achats WHERE date_livraison_prevue IS NOT NULL AND date_livraison_reelle IS NULL)";
}

// Tri
$sql_sort = $sort;
// Mapping
if ($sort === 'ref_interne') $sql_sort = 'ref_display';
if ($sort === 'date_commande') $sql_sort = 'date_cmd';
// fournisseur_nom, nom_affaire, total_ht, statut work directly on alias

$sql .= " ORDER BY $sql_sort $order LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll();

// CALCUL DU STATUT DYNAMIQUE pour chaque commande
foreach ($commandes as &$cmd) {
    // Pour les commandes EXPRESS, on utilise created_at comme date de création
    $cmd['statut_dynamique'] = calculate_order_status($cmd);
}
unset($cmd); // Break reference

$page_title = 'Commandes Fournisseurs';
require_once 'header.php';
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<style>
    /* SPLIT TABLE LAYOUT */
    html, body { height: 100%; overflow: hidden; }
    .main-content {
        height: 100vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        padding-top: 50px; /* Standard navbar offset */
    }
    .page-header-area {
        flex: 0 0 auto;
        padding: 1rem 1.5rem 0 1.5rem; /* Match Affaires mt-3 (1rem) */
    }

    /* CONTAINER PRINCIPAL */
    .table-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden; /* No global scroll */
        padding: 0 1.5rem 1.5rem 1.5rem;
        min-height: 0;
    }
    
    /* HEADER WRAPPER (Fixed) */
    .header-wrapper {
        flex: 0 0 auto;
        padding-right: 0; /* Handled by JS */
        background: var(--bs-tertiary-bg); /* Adaptive BG */
        border: 1px solid var(--bs-border-color);
        border-bottom: none;
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
        overflow: hidden; /* No scroll */
    }

    /* BODY WRAPPER (Scrollable) */
    .body-wrapper {
        flex: 1;
        overflow-y: auto; /* Scroll bar here */
        border: 1px solid var(--bs-border-color);
        border-top: none;
        border-bottom-left-radius: 0.5rem;
        border-bottom-right-radius: 0.5rem;
        background: transparent; /* Transparent for Dark Mode compat */
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
        height: 60px;
        vertical-align: middle;
    }
    .header-wrapper tr:nth-child(2) th {
        padding: 0.5rem;
        height: auto;
        background-color: var(--bs-tertiary-bg);
    }
    
    /* Specific Body Styles */
    .body-wrapper td {
        border-top: 1px solid #dee2e6;
        padding: 0.75rem 0.5rem;
    }

    /* Select2 Fixes */
    .select2-container--bootstrap-5 .select2-selection { border-color: #dee2e6; }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
    .select2-container .select2-selection--single { height: 31px !important; }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height: 29px !important; font-size: 0.875rem; }
    
    /* Filter Inputs - FORCE Better Contrast */
    .header-wrapper tr:nth-child(2) input[type="text"].form-control,
    .header-wrapper tr:nth-child(2) input[type="month"].form-control,
    .header-wrapper tr:nth-child(2) select.form-select {
        background-color: #34495e !important;
        color: #ecf0f1 !important;
        border: 1px solid #5d6d7e !important;
        font-size: 0.875rem !important;
        font-weight: 500 !important;
    }
    .header-wrapper tr:nth-child(2) input[type="text"].form-control::placeholder,
    .header-wrapper tr:nth-child(2) input[type="month"].form-control::placeholder {
        color: #bdc3c7 !important;
        opacity: 1 !important;
    }
    .header-wrapper tr:nth-child(2) input.form-control:focus,
    .header-wrapper tr:nth-child(2) select.form-select:focus {
        background-color: #2c3e50 !important;
        color: #ffffff !important;
        border-color: #3498db !important;
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.3) !important;
    }
    .header-wrapper tr:nth-child(2) select.form-select option {
        background-color: #2c3e50 !important;
        color: #ffffff !important;
    }
    
    /* Rows clickable */
    tbody tr.clickable-row { cursor: pointer; transition: background-color 0.15s; }
    tbody tr.clickable-row:hover { background-color: rgba(0,0,0,0.03) !important; }
</style>

<div class="main-content">
    
    <!-- HEADER AREA FIXE -->
    <div class="page-header-area">
        
        <!-- TITRE & BOUTON (Match Affaires mb-4) -->
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h2 class="fw-bold mb-0 text-white"><i class="fas fa-truck text-info me-2"></i>Commandes Fournisseurs</h2>
             <a href="commandes_saisie.php" class="btn btn-petrol rounded-pill shadow-sm fw-bold">
                <i class="fas fa-plus-circle me-2"></i>Nouvelle Commande
            </a>
        </div>

        <!-- SEARCH BAR (Match Affaires mb-4, p-3) -->
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body p-3">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md flex-grow-1">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control border-0 bg-light" placeholder="Chercher une commande (Réf, Fournisseur, Affaire)..." value="<?= h($search) ?>">
                        </div>
                    </div>
                    <div class="col-6 col-md-auto d-grid">
                        <button type="submit" class="btn btn-secondary shadow-sm fw-bold px-3">FILTRER</button>
                    </div>
                    <?php if($search || $f_date || $f_ref || $f_fournisseur || $f_affaire || $f_statut): ?>
                    <div class="col-6 col-md-auto d-grid">
                         <a href="commandes_liste.php" class="btn btn-outline-danger shadow-sm"><i class="fas fa-times"></i></a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- TABLE AREA SPLIT -->
    <div class="table-container">
        
        <?php
        // DEFINITION DES LARGEURS DE COLONNES (Shared Colgroup)
        // Date: 130px, Action: 100px. Others % based.
        // Total ~ 100%. 
        // Force simple % approach for stability.
        $colgroup = '
        <colgroup>
            <col style="width: 10%;"> <!-- Date -->
            <col style="width: 14%;"> <!-- Affaire -->
            <col style="width: 12%;"> <!-- Ref -->
            <col style="width: 18%;"> <!-- Fourn -->
            <col style="width: 19%;"> <!-- Des (Reduced 21->19) -->
            <col style="width: 10%;"> <!-- Mt -->
            <col style="width: 8%;">  <!-- Statut -->
            <col style="width: 9%;">  <!-- Action (Increased 7->9) -->
        </colgroup>';
        ?>

        <!-- 1. HEADER FIXED -->
        <div class="header-wrapper shadow-sm">
            <table class="table mb-0 table-split">
                <?= $colgroup ?>
                <thead>
                    <!-- LIGNE 1 : TITRES -->
                    <tr>
                        <th><?= sort_link('date_commande', 'Date Création', $sort, $order) ?></th>
                        <th><?= sort_link('nom_affaire', 'N° Affaire', $sort, $order) ?></th>
                        <th><?= sort_link('ref_interne', 'N° Commande', $sort, $order) ?></th>
                        <th><?= sort_link('fournisseur_nom', 'Fournisseur', $sort, $order) ?></th>
                        <th>Désignation</th>
                        <th class="text-end"><?= sort_link('total_ht', 'Montant HT', $sort, $order) ?></th>
                        <th class="text-center"><?= sort_link('statut', 'Statut', $sort, $order) ?></th>
                        <th class="text-end">Action</th>
                    </tr>
                    <!-- LIGNE 2 : FILTRES -->
                    <tr>
                         <th class="p-1">
                             <select class="form-select form-select-sm select2-filter" name="f_date" data-placeholder="Mois..." style="background-color: #ffffff !important; color: #000000 !important; border: 1px solid #ced4da !important;">
                                <option value=""></option>
                                <?php foreach($opt_dates as $ym): ?>
                                    <option value="<?= h($ym) ?>" <?= $f_date == $ym ? 'selected' : '' ?>><?= h($ym) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </th>
                        <th class="p-1">
                            <select class="form-select form-select-sm select2-filter" name="f_affaire" data-placeholder="Affaire..." style="background-color: #ffffff !important; color: #000000 !important; border: 1px solid #ced4da !important;">
                                <option value=""></option>
                                <?php foreach($opt_affaires as $nom): ?>
                                    <option value="<?= h($nom) ?>" <?= $f_affaire == $nom ? 'selected' : '' ?>><?= h($nom) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </th>
                        <th class="p-1">
                            <select class="form-select form-select-sm select2-filter" name="f_ref" data-placeholder="Réf..." style="background-color: #ffffff !important; color: #000000 !important; border: 1px solid #ced4da !important;">
                                <option value=""></option>
                                <?php foreach($opt_refs as $r): ?>
                                    <option value="<?= h($r) ?>" <?= $f_ref == $r ? 'selected' : '' ?>><?= h($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </th>
                        <th class="p-1">
                            <select class="form-select form-select-sm select2-filter" name="f_fournisseur" data-placeholder="Fournisseur..." style="background-color: #ffffff !important; color: #000000 !important; border: 1px solid #ced4da !important;">
                                <option value=""></option>
                                <?php foreach($opt_fournisseurs as $nom): ?>
                                    <option value="<?= h($nom) ?>" <?= $f_fournisseur == $nom ? 'selected' : '' ?>><?= h($nom) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </th>
                        <th class="p-1"></th> <!-- Désignation -->
                        <th class="p-1"></th> <!-- Montant -->
                        <th class="p-1">
                            <select class="form-select form-select-sm select2-filter" name="f_statut" data-placeholder="Statut..." style="background-color: #ffffff !important; color: #000000 !important; border: 1px solid #ced4da !important;">
                                <option value=""></option>
                                <?php foreach(['En Attente', 'Commandée', 'ARC Reçu', 'Livraison Prévue', 'Livrée'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $f_statut === $st ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
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
                    <?php if (empty($commandes)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">Aucune commande trouvée.</td></tr>
                    <?php else: ?>
                        <?php foreach ($commandes as $cmd): ?>
                            <?php 
                                $viewUrl = "commandes_detail.php?id=" . $cmd['original_id'];
                                if ($cmd['source_type'] === 'EXPRESS') {
                                    $viewUrl .= "&type=EXPRESS";
                                }
                                $target = "_self"; // Always open in same tab now
                            ?>
                            <tr class="clickable-row" onclick="window.location.href='<?= $viewUrl ?>'">
                                <td class="text-muted small"><?= $cmd['date_cmd'] ? date('d/m/Y', strtotime($cmd['date_cmd'])) : '-' ?></td>
                                <td>
                                    <?php if ($cmd['nom_affaire']): ?>
                                        <div class="fw-bold text-dark text-truncate" style="font-size: 0.9rem;"><?= h($cmd['nom_affaire']) ?></div>
                                        <div class="text-muted small text-truncate">#<?= h($cmd['numero_prodevis']) ?></div>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-bold <?= $cmd['source_type'] === 'EXPRESS' ? 'text-danger' : 'text-petrol' ?>"><?= h($cmd['ref_display']) ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-ph rounded-circle bg-light text-primary d-flex align-items-center justify-content-center border" style="width:28px;height:28px;font-size:0.75rem;">
                                            <?= strtoupper(substr($cmd['fournisseur_nom'] ?? '?', 0, 1)) ?>
                                        </div>
                                        <div class="text-truncate"><?= h($cmd['fournisseur_nom'] ?? 'Inconnu') ?></div>
                                    </div>
                                </td>
                                <td><small class="text-muted text-truncate d-block"><?= h($cmd['designation'] ?? '') ?></small></td>
                                <td class="text-end fw-bold"><?= number_format($cmd['total_ht'] ?? 0, 2, ',', ' ') ?> €</td>
                                <td class="text-center"><?= badge_statut($cmd['statut_dynamique']) ?></td>
                                <td class="text-end" onclick="event.stopPropagation()">
                                    <div class="btn-group">
                                        <?php if ($cmd['source_type'] === 'STANDARD'): ?>
                                            <a href="commandes_saisie.php?id=<?= $cmd['original_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-pen"></i></a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCommande(<?= $cmd['original_id'] ?>, '<?= $cmd['ref_display'] ?>')"><i class="fas fa-trash"></i></button>
                                        <?php else: ?>
                                            <!-- Express Actions -->
                                            <a href="<?= $viewUrl ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                            <!-- Delete logic for Express can be added here if needed, consistent with Detail page -->
                                        <?php endif; ?>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    url.searchParams.delete('page'); 
    window.location.href = url.toString();
}

function deleteCommande(id, ref) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Vous allez supprimer la commande " + ref + " et ses lignes.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer !',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('commandes_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_commande', id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Supprimée !', 'Fait.', 'success')
                    .then(() => window.location.reload());
                } else {
                    Swal.fire('Erreur', data.message, 'error');
                }
            });
        }
    })
}
    // SYNC SCROLL (Horizontal)
    $('.body-wrapper').on('scroll', function() { 
        $('.header-wrapper').scrollLeft($(this).scrollLeft()); 
    });

    // ADJUST SCROLLBAR PADDING (Dynamic)
    function adjustHeaderPadding() {
        var $body = $('.body-wrapper');
        var $header = $('.header-wrapper');
        var scrollbarWidth = $body[0].offsetWidth - $body[0].clientWidth;
        $header.css('padding-right', scrollbarWidth + 'px');
    }
    
    $(window).on('resize', adjustHeaderPadding);
    adjustHeaderPadding();
    
    // Clear old filter values from browser storage (one-time cleanup)
    if (localStorage.getItem('commandes_filter_cleanup') !== 'done') {
        localStorage.removeItem('commandes_filters');
        sessionStorage.removeItem('commandes_filters');
        localStorage.setItem('commandes_filter_cleanup', 'done');
    }
    
    // FORCE Filter Input Styling with MAXIMUM PRIORITY (cssText override)
    function forceFilterStyling() {
        const filterInputs = document.querySelectorAll('.header-wrapper tr:nth-child(2) input, .header-wrapper tr:nth-child(2) select');
        filterInputs.forEach(input => {
            input.style.cssText = 'background-color: #34495e !important; color: #ecf0f1 !important; border: 1px solid #5d6d7e !important; font-weight: 500 !important;';
        });
    }
    
    // Execute immediately
    $(document).ready(function() {
        forceFilterStyling();
        
        // Re-apply after 500ms (in case Select2 initializes)
        setTimeout(forceFilterStyling, 500);
        
        // Apply on focus/blur
        $(document).on('focus', '.header-wrapper tr:nth-child(2) input, .header-wrapper tr:nth-child(2) select', function() {
            this.style.cssText = 'background-color: #2c3e50 !important; color: #ffffff !important; border: 1px solid #3498db !important; font-weight: 500 !important;';
        });
        
        $(document).on('blur', '.header-wrapper tr:nth-child(2) input, .header-wrapper tr:nth-child(2) select', function() {
            this.style.cssText = 'background-color: #34495e !important; color: #ecf0f1 !important; border: 1px solid #5d6d7e !important; font-weight: 500 !important;';
        });
    });
</script>
<?php require_once 'footer.php'; ?>

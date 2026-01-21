<?php
// clients_liste.php - Annuaire Clients (REFONTE MOBILE + FILTRES SELECT2)
$page_title = "Clients";
require_once 'controllers/clients_controller.php';
require_once 'header.php';

// Note: controllers/clients_controller.php gère déjà la récupération $clients_list
// On va devoir filtrer en PHP ou adapter si on voulait du SQL, 
// mais pour l'instant on réplique le front.
// Si le controller ne gère pas les filtres avancés, on va les faire en JS ou adapter le controller ?
// Hypothèse : On refait la logique SQL ici pour masteriser les filtres comme sur Affaires.

require_once 'db.php';
require_once 'functions.php';

// 1. GESTION DU TRI & FILTRES
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'nom_principal';
$order = $_GET['order'] ?? 'ASC';

// Whitelist tri
$valid_sorts = ['nom_principal', 'ville', 'code_postal', 'code_client', 'date_creation'];
if (!in_array($sort, $valid_sorts)) $sort = 'nom_principal';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Filtres Colonnes
$f_nom = trim($_GET['f_nom'] ?? '');
$f_ville = trim($_GET['f_ville'] ?? '');
$f_code = trim($_GET['f_code'] ?? '');

function sort_link($field, $label, $current_sort, $current_order) {
    global $search, $f_nom, $f_ville, $f_code;
    $new_order = ($current_sort === $field && $current_order === 'ASC') ? 'DESC' : 'ASC';
    $icon = '';
    if ($current_sort === $field) {
        $icon = ($current_order === 'ASC') ? ' <i class="fas fa-sort-down"></i>' : ' <i class="fas fa-sort-up"></i>';
    }
    $query = http_build_query(array_merge($_GET, ['sort' => $field, 'order' => $new_order]));
    return "<a href='?$query' class='text-white text-decoration-none'>$label$icon</a>";
}

// 2. RÉCUPÉRATION DES DROPDOWNS
try {
    $opt_noms = $pdo->query("SELECT DISTINCT nom_principal FROM clients ORDER BY nom_principal")->fetchAll(PDO::FETCH_COLUMN);
    $opt_villes = $pdo->query("SELECT DISTINCT ville FROM clients WHERE ville IS NOT NULL AND ville != '' ORDER BY ville")->fetchAll(PDO::FETCH_COLUMN);
    // Codes clients : on prend les codes existants
    $opt_codes = $pdo->query("SELECT DISTINCT COALESCE(code_client, CONCAT('CLI-', LPAD(id, 3, '0'))) as code FROM clients ORDER BY code")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $opt_noms = []; $opt_villes = []; $opt_codes = [];
}

// 3. REQUÊTE PRINCIPALE
$params = [];
$sql = "SELECT * FROM clients WHERE 1=1";

if ($search) {
    $sql .= " AND (nom_principal LIKE ? OR ville LIKE ? OR email_principal LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Filtres Colonnes
if ($f_nom) { $sql .= " AND nom_principal LIKE ?"; $params[] = "%$f_nom%"; }
if ($f_ville) { $sql .= " AND ville LIKE ?"; $params[] = "%$f_ville%"; }
if ($f_code) { 
    // Recherche un peu loose sur le code
    $sql .= " AND (code_client LIKE ? OR CONCAT('CLI-', LPAD(id, 3, '0')) LIKE ?)"; 
    $params[] = "%$f_code%";
    $params[] = "%$f_code%";
}

$sql .= " ORDER BY $sort $order LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clients_list = $stmt->fetchAll();

?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .select2-container--bootstrap-5 .select2-selection { border-color: #dee2e6; }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
    .select2-container .select2-selection--single { height: 31px !important; }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height: 29px !important; font-size: 0.875rem; }
    /* Mobile Card overrides already in antigravity.css */
</style>

<div class="main-content">
    <div class="container-fluid px-2 px-md-4 mt-3">

        <!-- ACTIONS -->
        <div class="d-flex justify-content-end align-items-center mb-4">
            <a href="clients_fiche.php" class="btn btn-petrol rounded-pill shadow-sm">
                <i class="fas fa-plus-circle me-2"></i>Nouveau Client
            </a>
        </div>

        <!-- SEARCH BAR -->
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body p-3">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md flex-grow-1">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control border-0 bg-light" placeholder="Chercher un client..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-6 col-md-auto d-grid">
                        <button type="submit" class="btn btn-secondary shadow-sm">Filtrer</button>
                    </div>
                    <?php if($search || $f_nom || $f_ville || $f_code): ?>
                    <div class="col-6 col-md-auto d-grid">
                         <a href="clients_liste.php" class="btn btn-outline-danger shadow-sm"><i class="fas fa-times"></i></a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- ALERTES (conservées) -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'created'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>Client créé avec succès.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- TABLEAU CLIENTS -->
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-ag-theme table-mobile-cards">
                    <thead>
                        <tr>
                            <th><?= sort_link('nom_principal', 'Nom Principal', $sort, $order) ?></th>
                            <th>Contact</th>
                            <th><?= sort_link('ville', 'Adresse', $sort, $order) ?></th>
                            <th><?= sort_link('code_client', 'Code', $sort, $order) ?></th>
                            <th class="text-end">Actions</th>
                        </tr>
                        <!-- FILTRES SELECT2 (Desktop) -->
                        <tr class="d-none d-md-table-row bg-light">
                            <th class="p-1" style="min-width: 150px;">
                                <select class="form-select form-select-sm select2-filter" name="f_nom" data-placeholder="Nom...">
                                    <option value=""></option>
                                    <?php foreach($opt_noms as $nm): ?>
                                        <option value="<?= h($nm) ?>" <?= $f_nom == $nm ? 'selected' : '' ?>><?= h($nm) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </th>
                            <th class="p-1"></th> <!-- Contact -->
                            <th class="p-1" style="min-width: 150px;">
                                <select class="form-select form-select-sm select2-filter" name="f_ville" data-placeholder="Ville...">
                                    <option value=""></option>
                                    <?php foreach($opt_villes as $v): ?>
                                        <option value="<?= h($v) ?>" <?= $f_ville == $v ? 'selected' : '' ?>><?= h($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </th>
                            <th class="p-1" style="min-width: 100px;">
                                <!-- Code Client -->
                                <select class="form-select form-select-sm select2-filter" name="f_code" data-placeholder="Code...">
                                    <option value=""></option>
                                    <?php foreach($opt_codes as $cd): ?>
                                        <option value="<?= h($cd) ?>" <?= $f_code == $cd ? 'selected' : '' ?>><?= h($cd) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </th>
                            <th class="p-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients_list)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">Aucun client trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach ($clients_list as $c): ?>
                                <tr onclick="window.location='clients_fiche.php?id=<?= $c['id'] ?>';" style="cursor: pointer;">
                                    <td data-label="Nom Principal">
                                        <div class="fw-bold text-dark">
                                            <?= h(($c['civilite'] ?? '') . ' ' . ($c['prenom'] ?? '') . ' ' . $c['nom_principal']) ?>
                                        </div>
                                    </td>
                                    <td data-label="Contact">
                                        <?php if($c['email_principal']): ?>
                                            <div class="text-muted small"><i class="fas fa-envelope me-1"></i> <?= h($c['email_principal']) ?></div>
                                        <?php endif; ?>
                                        <?php if($c['telephone_fixe']): ?>
                                            <div class="text-muted small"><i class="fas fa-phone me-1"></i> <?= h($c['telephone_fixe']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Adresse">
                                        <?php if($c['ville']): ?>
                                            <div class="text-dark fw-medium"><?= h($c['ville']) ?></div>
                                            <small class="text-muted"><?= h($c['code_postal']) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Code">
                                        <span class="badge bg-light border text-dark">
                                            <?= isset($c['code_client']) ? h($c['code_client']) : 'CLI-'.str_pad($c['id'], 3, '0', STR_PAD_LEFT) ?>
                                        </span>
                                    </td>
                                    <td data-label="Actions" class="text-end" onclick="event.stopPropagation();">
                                        <a href="controllers/clients_controller.php?del=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Confirmer la suppression ?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
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
    url.searchParams.delete('page');
    window.location.href = url.toString();
}
</script>
<?php require_once 'footer.php'; ?>

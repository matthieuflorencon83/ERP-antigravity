<?php
// fournisseurs_liste.php - Liste des Fournisseurs (REFONTE MOBILE + FILTRES SELECT2)
$page_title = "Liste des Fournisseurs";
require_once 'db.php';
require_once 'functions.php';

// Initialisation de la session si besoin
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

require_once 'header.php';

// 1. GESTION DU TRI & FILTRES
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'nom';
$order = $_GET['order'] ?? 'ASC';

// Whitelist tri
$valid_sorts = ['code_fou', 'nom', 'ville', 'nb_contacts', 'nb_commandes'];
if (!in_array($sort, $valid_sorts)) $sort = 'nom';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Filtres Colonnes
$f_nom = trim($_GET['f_nom'] ?? '');
$f_ville = trim($_GET['f_ville'] ?? '');

function sort_link($field, $label, $current_sort, $current_order) {
    global $search, $f_nom, $f_ville;
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
    $opt_noms = $pdo->query("SELECT DISTINCT nom FROM fournisseurs ORDER BY nom")->fetchAll(PDO::FETCH_COLUMN);
    $opt_villes = $pdo->query("SELECT DISTINCT ville FROM fournisseurs WHERE ville IS NOT NULL AND ville != '' ORDER BY ville")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $opt_noms = []; $opt_villes = [];
}

// 3. REQUÊTE PRINCIPALE
$params = [];
$sql = "
    SELECT f.*,
           (SELECT COUNT(*) FROM fournisseur_contacts fc WHERE fc.fournisseur_id = f.id) as nb_contacts,
           (SELECT COUNT(*) FROM commandes_achats ca WHERE ca.fournisseur_id = f.id) as nb_commandes
    FROM fournisseurs f
    WHERE 1=1
";

if ($search) {
    $sql .= " AND (f.nom LIKE ? OR f.code_fou LIKE ? OR f.ville LIKE ? OR f.email_general LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Filtres Colonnes
if ($f_nom) { $sql .= " AND f.nom LIKE ?"; $params[] = "%$f_nom%"; }
if ($f_ville) { $sql .= " AND f.ville LIKE ?"; $params[] = "%$f_ville%"; }

$sql .= " ORDER BY $sort $order LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .select2-container--bootstrap-5 .select2-selection { border-color: #dee2e6; }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
    .select2-container .select2-selection--single { height: 31px !important; }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height: 29px !important; font-size: 0.875rem; }
</style>

<div class="main-content">
    <div class="container-fluid px-2 px-md-4 mt-3">
    
        <!-- ACTIONS -->
        <div class="d-flex justify-content-end align-items-center mb-4">
            <a href="fournisseurs_detail.php?new=1" class="btn btn-petrol rounded-pill shadow-sm">
                <i class="fas fa-plus-circle me-2"></i>Nouveau Fournisseur
            </a>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i>Fournisseur supprimé avec succès.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= $_SESSION['error'] ?? "Une erreur est survenue." ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- SEARCH BAR -->
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body p-3">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md flex-grow-1">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control border-0 bg-light" placeholder="Chercher un fournisseur (Nom, Code, Ville)..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-6 col-md-auto d-grid">
                        <button type="submit" class="btn btn-secondary shadow-sm">Filtrer</button>
                    </div>
                    <?php if($search || $f_nom || $f_ville): ?>
                    <div class="col-6 col-md-auto d-grid">
                         <a href="fournisseurs_liste.php" class="btn btn-outline-danger shadow-sm"><i class="fas fa-times"></i></a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- TABLEAU FOURNISSEURS -->
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-ag-theme table-mobile-cards">
                    <thead>
                        <tr>
                            <th><?= sort_link('code_fou', 'Code', $sort, $order) ?></th>
                            <th><?= sort_link('nom', 'Raison Sociale', $sort, $order) ?></th>
                            <th><?= sort_link('ville', 'Ville', $sort, $order) ?></th>
                            <th>Email Général</th>
                            <th class="text-center"><?= sort_link('nb_contacts', 'Contacts', $sort, $order) ?></th>
                            <th class="text-center"><?= sort_link('nb_commandes', 'Commandes', $sort, $order) ?></th>
                            <th class="text-end">Actions</th>
                        </tr>
                        <!-- FILTRES SELECT2 (Desktop) -->
                        <tr class="d-none d-md-table-row bg-light">
                            <th class="p-1"></th> <!-- Code -->
                            <th class="p-1" style="min-width: 200px;">
                                <select class="form-select form-select-sm select2-filter" name="f_nom" data-placeholder="Nom...">
                                    <option value=""></option>
                                    <?php foreach($opt_noms as $nm): ?>
                                        <option value="<?= h($nm) ?>" <?= $f_nom == $nm ? 'selected' : '' ?>><?= h($nm) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </th>
                            <th class="p-1" style="min-width: 150px;">
                                <select class="form-select form-select-sm select2-filter" name="f_ville" data-placeholder="Ville...">
                                    <option value=""></option>
                                    <?php foreach($opt_villes as $v): ?>
                                        <option value="<?= h($v) ?>" <?= $f_ville == $v ? 'selected' : '' ?>><?= h($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </th>
                            <th class="p-1"></th> <!-- Email -->
                            <th class="p-1"></th> <!-- Nb Contacts -->
                            <th class="p-1"></th> <!-- Nb Commandes -->
                            <th class="p-1"></th> <!-- Actions -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fournisseurs)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">Aucun fournisseur trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach ($fournisseurs as $f): ?>
                                <tr onclick="window.location.href='fournisseurs_detail.php?id=<?= $f['id'] ?>'" style="cursor:pointer;">
                                    <td data-label="Code">
                                        <span class="badge bg-secondary"><?= htmlspecialchars($f['code_fou'] ?? '-') ?></span>
                                    </td>
                                    <td data-label="Raison Sociale">
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($f['nom']) ?></div>
                                    </td>
                                    <td data-label="Ville">
                                        <?= htmlspecialchars($f['ville'] ?? '-') ?>
                                    </td>
                                    <td data-label="Email">
                                        <?php if (!empty($f['email_general'])): ?>
                                            <a href="mailto:<?= htmlspecialchars($f['email_general']) ?>" class="text-decoration-none text-muted small" onclick="event.stopPropagation()">
                                                <i class="far fa-envelope me-1"></i> <?= htmlspecialchars($f['email_general']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Contacts" class="text-center">
                                        <?php if ($f['nb_contacts'] > 0): ?>
                                            <span class="badge bg-info text-dark rounded-pill"><?= $f['nb_contacts'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Commandes" class="text-center">
                                        <?php if ($f['nb_commandes'] > 0): ?>
                                            <span class="badge bg-primary rounded-pill"><?= $f['nb_commandes'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Actions" class="text-end">
                                        <a href="fournisseur_actions.php?action=delete&id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-danger border-0" onclick="event.stopPropagation(); return confirm('Supprimer ce fournisseur ?')">
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
    window.location.href = url.toString();
}
</script>
<?php require_once 'footer.php'; ?>

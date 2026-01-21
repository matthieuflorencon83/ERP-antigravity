<?php
// catalogue_liste.php - Base Articles & Stock
session_start();
require_once 'db.php';
require_once 'functions.php';

// 1. RECHERCHE & FILTRES
$search = trim($_GET['q'] ?? '');
$famille = $_GET['famille'] ?? '';
$params = [];

$sql = "SELECT a.*, f.nom as nom_fournisseur 
        FROM articles a 
        LEFT JOIN fournisseurs f ON a.fournisseur_prefere_id = f.id 
        WHERE 1=1";

if ($search) {
    $sql .= " AND (a.reference_interne LIKE ? OR a.designation LIKE ? OR a.ref_fournisseur LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

if ($famille) {
    $sql .= " AND a.famille = ?";
    $params[] = $famille;
}

$sql .= " ORDER BY a.designation ASC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();

// Récupération des familles pour le filtre
$familles = $pdo->query("SELECT DISTINCT famille FROM articles WHERE famille IS NOT NULL ORDER BY famille")->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Catalogue';
require_once 'header.php';
?>

<div class="main-content">
    
    <!-- Hero / Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Catalogue</h1>
        <a href="catalogue_detail.php" class="btn btn-primary rounded-pill shadow-sm">
            <i class="fas fa-plus-circle me-2"></i>Nouvel Article
        </a>
    </div>

    <!-- ALERTES -->
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-trash-alt me-2"></i>Article supprimé avec succès.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php if($_GET['error'] == 'constraint'): ?>
                Impossible de supprimer cet article (utilisé dans des commandes).
            <?php else: ?>
                Une erreur est survenue lors de la suppression.
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-body border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Réf, Désignation..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="famille" class="form-select" onchange="this.form.submit()">
                        <option value="">Toutes les familles</option>
                        <?php foreach($familles as $f): ?>
                            <option value="<?= $f ?>" <?= $f === $famille ? 'selected' : '' ?>><?= $f ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-secondary">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-ag-theme">
                <thead>
                    <tr>
                        <th width="50" class="text-white bg-primary border-primary">Img</th>
                        <th class="text-white bg-primary border-primary">Référence</th>
                        <th class="text-white bg-primary border-primary">Désignation</th>
                        <th class="text-white bg-primary border-primary">Fournisseur</th>
                        <th class="text-center text-white bg-primary border-primary">Stock</th>
                        <th class="text-end text-white bg-primary border-primary">Prix Achat</th>
                        <th class="text-center text-white bg-primary border-primary">Unité</th>
                        <th class="text-end text-white bg-primary border-primary">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($articles as $art): ?>
                    <tr style="cursor:pointer" onclick="window.location='catalogue_detail.php?id=<?= $art['id'] ?>'">
                        <td class="text-center">
                            <?php if($art['image_path']): ?>
                                <img src="view.php?path=<?= urlencode($art['image_path']) ?>" width="40" height="40" class="rounded object-fit-cover shadow-sm">
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center border" style="width:40px; height:40px;">
                                    <i class="fas fa-cube text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold text-primary">
                            <?= htmlspecialchars($art['reference_interne']) ?>
                            <?php if($art['ref_fournisseur']): ?>
                                <div class="small text-muted fw-normal"><i class="fas fa-tag me-1"></i><?= htmlspecialchars($art['ref_fournisseur']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-medium"><?= htmlspecialchars($art['designation']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($art['famille'] ?? '') ?></small>
                        </td>
                        <td>
                            <?php if($art['nom_fournisseur']): ?>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($art['nom_fournisseur']) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php 
                                $stock = floatval($art['stock_physique'] ?? 0);
                                $seuil = floatval($art['seuil_alerte_stock'] ?? 0);
                                $color = $stock <= $seuil ? 'text-danger fw-bold' : 'text-success fw-bold';
                            ?>
                            <span class="<?= $color ?>"><?= number_format($stock, 2) ?></span>
                        </td>
                        <td class="text-end font-monospace">
                            <?= number_format($art['prix_achat_ht'], 2) ?> €
                        </td>
                        <td class="text-center small text-muted">
                            / <?= htmlspecialchars($art['unite_stock'] ?? 'U') ?>
                        </td>
                        <td class="text-end">
                            <a href="catalogue_actions.php?action=delete&id=<?= $art['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="event.stopPropagation(); return confirm('Supprimer définitivement cet article ?');" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if(empty($articles)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i><br>
                                Aucun article trouvé. Commencez par en créer un !
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


</body>
</html>

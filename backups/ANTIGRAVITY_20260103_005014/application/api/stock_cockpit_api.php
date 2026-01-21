<?php
/**
 * api/stock_cockpit_api.php
 * Endpoint AJAX pour le Cockpit Logistique
 */
require_once '../auth.php';
require_once '../db.php';
require_once '../controllers/stocks_controller.php';

header('Content-Type: application/json');

$controller = new StocksController($pdo);
$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'get_details') {
        // Retourne le HTML du panneau de droite
        $id = (int)$_GET['id'];
        
        // 1. Info Article (Hack: on réutilise getInventory filtré ou on fait une query simple)
        // Optimisation: Query spécifique
        $stmt = $pdo->prepare("SELECT ac.*, four.nom as nom_fournisseur 
                              FROM articles ac 
                              LEFT JOIN fournisseurs four ON ac.fournisseur_prefere_id = four.id
                              WHERE ac.id = ?");
        $stmt->execute([$id]);
        $art = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$art) throw new Exception("Article introuvable");

        // 2. Stock par Emplacement
        $locations = $controller->getStockByLocations($id);
        $total_stock = array_sum($locations);

        // 3. Historique
        $history = $controller->getArticleHistory($id, 10);

        // Rendu HTML (Fragment)
        header('Content-Type: text/html');
        ?>
        <div class="p-4 animate__animated animate__fadeInRight">
            <!-- Header Fiche -->
            <div class="d-flex align-items-start gap-3 mb-4">
                <div class="bg-body-secondary rounded p-3 text-center" style="min-width: 80px;">
                    <i class="fas fa-cube fa-2x text-petrol"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($art['designation']) ?></h4>
                    <div class="text-body-secondary font-monospace"><?= htmlspecialchars($art['ref_fournisseur'] ?? $art['reference_interne']) ?></div>
                    <span class="badge bg-secondary"><?= htmlspecialchars($art['famille']) ?></span>
                </div>
            </div>

            <!-- Jauge Stock (Bandeau Premium) -->
            <div class="card border-0 shadow-sm mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f4c75 0%, #3282b8 100%); color: white;">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-start z-1 position-relative">
                        <div>
                            <div class="text-uppercase small fw-bold opacity-75 mb-1">Stock Disponible</div>
                            <div class="display-5 fw-bold"><?= $total_stock ?> <span class="fs-6 opacity-50">u</span></div>
                        </div>
                        <i class="fas fa-cubes fa-3x opacity-25"></i>
                    </div>
                    
                    <!-- Détail Locations -->
                    <div class="mt-3 pt-3 border-top border-white border-opacity-25 d-flex gap-2 flex-wrap position-relative z-1">
                        <?php foreach($locations as $loc => $qte): ?>
                            <span class="badge bg-white bg-opacity-25 border border-white border-opacity-25 text-white">
                                <i class="fas fa-map-marker-alt me-1"></i><?= $loc ?> : <b><?= $qte ?></b>
                            </span>
                        <?php endforeach; ?>
                        <?php if(empty($locations)): ?>
                            <span class="badge bg-danger text-white">Rupture de stock</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Actions Rapides -->
            <div class="card border-0 bg-body-tertiary mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="fas fa-exchange-alt me-2"></i>Nouveau Mouvement</h6>
                    <form id="form-mouvement">
                        <div class="row g-2">
                            <div class="col-6">
                                <select name="type" class="form-select form-select-sm fw-bold">
                                    <option value="ENTREE" class="text-success">ENTRÉE (+)</option>
                                    <option value="SORTIE" class="text-danger">SORTIE (-)</option>
                                    <option value="INVENTAIRE" class="text-warning">INV. (Correction)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="emplacement" class="form-select form-select-sm">
                                    <option value="Atelier">Atelier (Défaut)</option>
                                    <!-- TODO: Dynamic Chantiers -->
                                </select>
                            </div>
                            <div class="col-8">
                                <input type="number" name="quantite" class="form-control form-control-sm" placeholder="Quantité" required step="0.01">
                            </div>
                            <div class="col-4 d-grid">
                                <button type="submit" class="btn btn-petrol btn-sm">Valider</button>
                            </div>
                            <div class="col-12 mt-2">
                                <input type="text" name="motif" class="form-control form-control-sm" placeholder="Motif (ex: Départ Chantier Dupont)">
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Timeline -->
            <h6 class="fw-bold mb-3 border-bottom pb-2">Historique Récent</h6>
            <div class="timeline small">
                <?php foreach($history as $h): ?>
                    <div class="d-flex gap-3 mb-2">
                        <div class="text-body-secondary" style="width: 80px;"><?= date('d/m H:i', strtotime($h['date_mouvement'])) ?></div>
                        <div>
                            <?php if($h['type_mouvement'] == 'ENTREE'): ?>
                                <span class="text-success fw-bold">+<?= $h['quantite'] ?></span>
                            <?php elseif($h['type_mouvement'] == 'INVENTAIRE'): ?>
                                <span class="text-warning fw-bold"><i class="fas fa-check-circle small me-1"></i> Inv. (<?= $h['quantite'] ?>)</span>
                            <?php else: ?>
                                <span class="text-danger fw-bold">-<?= $h['quantite'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($h['commentaire']) ?>">
                            <?= htmlspecialchars($h['commentaire'] ?: '-') ?>
                            <div class="text-body-secondary extra-small">par <?= htmlspecialchars($h['user_nom']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($history)): ?>
                    <div class="text-body-secondary fst-italic">Aucun mouvement récent.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        exit;
    }

    if ($action === 'move_stock') {
        // POST Action
        $article_id = (int)$_POST['article_id'];
        $type = $_POST['type'];
        $qty = (float)$_POST['quantite'];
        $motif = $_POST['motif'];
        $emplacement = $_POST['emplacement'] ?? 'Atelier';
        $user_id = $_SESSION['user_id'] ?? 1;

        // Finition ID ? (Simplification V1: NULL ou Défaut)
        $finition_id = null; // TODO: Gérer finition si l'article en a plusieurs

        $res = $controller->createMovement($article_id, $finition_id, $type, $qty, $user_id, $motif, null, $emplacement);
        
        // Récupérer le nouveau stock total pour mise à jour UI
        $locations = $controller->getStockByLocations($article_id);
        $new_total = array_sum($locations);

        echo json_encode(['success' => true, 'new_total' => $new_total]);
        exit;
    }

} catch (Exception $e) {
    if ($action === 'get_details') {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    } else {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

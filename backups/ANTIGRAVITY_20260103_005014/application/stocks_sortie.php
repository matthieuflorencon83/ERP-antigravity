<?php
// stocks_sortie.php - Assistant Sortie de Stock
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';
require_once 'controllers/stocks_controller.php';

$controller = new StocksController($pdo);
$error = '';
$success = '';

// 1. CHARGEMENT DONNEES
// Affaires en cours
$affaires = $pdo->query("SELECT id, nom_affaire, client_id FROM affaires WHERE statut != 'Terminé' ORDER BY nom_affaire")->fetchAll();
// Articles en stock (Pour le select)
$inventory = $controller->getInventory(); 

// 2. TRAITEMENT FORMULAIRE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $affaire_id = $_POST['affaire_id'];
        $stock_key = $_POST['stock_key']; // format "articleId_finitionId"
        $qty = (float) $_POST['quantite'];
        
        list($article_id, $finition_id) = explode('_', $stock_key);
        
        // Nom de l'affaire pour le commentaire
        $stmtA = $pdo->prepare("SELECT nom_affaire FROM affaires WHERE id=?");
        $stmtA->execute([$affaire_id]);
        $nom_affaire = $stmtA->fetchColumn();
        
        $motif = "Sortie Chantier : " . $nom_affaire;
        
        // Execution
        $controller->createMovement($article_id, $finition_id, 'SORTIE', $qty, $_SESSION['user_id'] ?? 1, $motif, $affaire_id);
        
        $success = "Sortie de stock enregistrée avec succès !";
        
    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

$page_title = 'Sortie de Stock';
require_once 'header.php';
?>

<div class="main-content">
    <div class="container mt-4">
        
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-dolly-flatbed me-2 text-danger"></i>Sortie de Matériel</h3>
                    <a href="stocks_liste.php" class="btn btn-outline-secondary">Annuler</a>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?= h($error) ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?= h($success) ?>
                        <div class="mt-2">
                             <a href="stocks_liste.php" class="btn btn-sm btn-success">Retour Stock</a>
                             <a href="stocks_sortie.php" class="btn btn-sm btn-outline-success">Nouvelle Sortie</a>
                        </div>
                    </div>
                <?php else: ?>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <form method="POST">
                            
                            <!-- 1. POUR QUEL CHANTIER ? -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Pour quelle Affaire ?</label>
                                <select name="affaire_id" class="form-select form-select-lg" required>
                                    <option value="">-- Sélectionner le chantier --</option>
                                    <?php foreach($affaires as $a): ?>
                                    <option value="<?= $a['id'] ?>"><?= h($a['nom_affaire']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- 2. QUEL ARTICLE ? -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Article à sortir</label>
                                <select name="stock_key" class="form-select" required>
                                    <option value="">-- Choisir dans le stock --</option>
                                    <?php foreach($inventory as $item): ?>
                                        <?php $key = $item['article_id'] . '_' . $item['finition_id']; ?>
                                        <option value="<?= $key ?>">
                                            <?= h($item['designation_commerciale']) ?> 
                                            (<?= h($item['nom_couleur']) ?>) 
                                            — Disp: <?= $item['quantite'] + 0 ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- 3. QUANTITE -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Quantité à prélever</label>
                                <input type="number" name="quantite" class="form-control form-control-lg" step="0.01" min="0.01" placeholder="0.00" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger btn-lg shadow rounded-pill">
                                    <i class="fas fa-sign-out-alt me-2"></i>Confirmer la Sortie
                                </button>
                            </div>
                            
                        </form>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        
    </div>
</div>

<?php require_once 'footer.php'; ?>

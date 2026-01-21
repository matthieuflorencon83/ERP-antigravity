<?php
/**
 * commandes_reception.php
 * Module Achats : Réception de Marchandise & Entrée en Stock.
 * 
 * @project Antigravity
 * @version 1.0
 */

require_once 'auth.php';
session_start();
require_once 'db.php';
require_once 'functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) die("Commande invalide.");

// ACTION : VALIDATION RECEPTION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reception') {
    csrf_require();
    
    try {
        $pdo->beginTransaction();

        // Récupération Ref Commande pour le motif
        $stmtCmd = $pdo->prepare("SELECT ref_interne FROM commandes_achats WHERE id = ?");
        $stmtCmd->execute([$id]);
        $ref_cmd = $stmtCmd->fetchColumn() ?: 'CMD #'.$id;
        
        $lignes_recues = $_POST['qte_recue'] ?? [];
        $tout_recu = true;
        
        // 1. Mise à jour Lignes + Stock
        foreach ($lignes_recues as $ligne_id => $qte_receptionnee) {
            if ($qte_receptionnee <= 0) continue; // Rien reçu sur cette ligne
            
            // Info Ligne
            $stmtL = $pdo->prepare("SELECT * FROM lignes_achat WHERE id = ?");
            $stmtL->execute([$ligne_id]);
            $ligne = $stmtL->fetch();
            
            if (!$ligne) continue;
            
            // Calcul Nouvelle Qté Reçue
            $nouvelle_qte_totale = $ligne['qte_recue'] + $qte_receptionnee;
            // Cap (Optionnel: on autorise le sur-stock ?) -> On autorise.
            
            // Update Ligne Achat
            $pdo->prepare("UPDATE lignes_achat SET qte_recue = ? WHERE id = ?")->execute([$nouvelle_qte_totale, $ligne_id]);
            
            // Update STOCK (Si article lié)
            if ($ligne['article_id']) {
                // Upsert Stock
                // On cherche si la ligne de stock existe (Article + Finition)
                // On suppose Emplacement = 'Atelier' par defaut
                $sqlStock = "INSERT INTO stocks (article_id, finition_id, quantite, emplacement) 
                             VALUES (?, ?, ?, 'Atelier') 
                             ON DUPLICATE KEY UPDATE quantite = quantite + VALUES(quantite)";
                $pdo->prepare($sqlStock)->execute([$ligne['article_id'], $ligne['finition_id'], $qte_receptionnee]);

                // TRACABILITE : Insert dans stocks_mouvements
                $sqlMvt = "INSERT INTO stocks_mouvements (article_id, finition_id, type_mouvement, quantite, date_mouvement, user_id, commentaire) 
                           VALUES (?, ?, 'ENTREE', ?, NOW(), ?, ?)";
                $motif_mvt = "Réception " . $ref_cmd;
                $user_id = $_SESSION['user_id'] ?? 1;
                $pdo->prepare($sqlMvt)->execute([$ligne['article_id'], $ligne['finition_id'], $qte_receptionnee, $user_id, $motif_mvt]);
            }
            
            // Check si tout est receptionné
            if ($nouvelle_qte_totale < $ligne['qte_commandee']) {
                $tout_recu = false;
            }
        }
        
        // 2. Update Statut Commande
        $statut_final = 'Partiellement Reçu';
        
        // Re-vérification globale (au cas où on n'a rien posté)
        // On compte les lignes non soldées
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM lignes_achat WHERE commande_id = ? AND qte_recue < qte_commandee");
        $stmtCheck->execute([$id]);
        $restant = $stmtCheck->fetchColumn();
        
        if ($restant == 0) {
            $statut_final = 'Reçu';
        }
        
        $pdo->prepare("UPDATE commandes_achats SET statut = ? WHERE id = ?")->execute([$statut_final, $id]);
        
        $pdo->commit();
        header("Location: commandes_detail.php?id=$id&success=reception_ok");
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur Réception : " . $e->getMessage();
    }
}

// AFFICHAGE
try {
    // Header
    $stmt = $pdo->prepare("
        SELECT c.*, f.nom as fournisseur_nom 
        FROM commandes_achats c
        JOIN fournisseurs f ON c.fournisseur_id = f.id
        WHERE c.id = ?
    ");
    $stmt->execute([$id]);
    $cmd = $stmt->fetch();
    
    if (!$cmd) die("Commande introuvable.");
    
    // Lignes
    $stmt = $pdo->prepare("
        SELECT l.*, ac.designation_commerciale, ac.ref_fournisseur, fin.nom_couleur 
        FROM lignes_achat l
        LEFT JOIN articles_catalogue ac ON l.article_id = ac.id
        LEFT JOIN finitions fin ON l.finition_id = fin.id
        WHERE l.commande_id = ?
    ");
    $stmt->execute([$id]);
    $lignes = $stmt->fetchAll();

} catch (Exception $e) {
    die("Erreur SQL : " . $e->getMessage());
}
$page_title = 'Réception Marchandise';
require_once 'header.php';
?>

    <div class="main-content">
        <div class="container mt-4">
            
            <div class="d-flex justify-content-end align-items-center mb-4">
                <a href="commandes_detail.php?id=<?= $id ?>" class="btn btn-outline-secondary">Retour à la commande</a>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="reception">
                        
                        <div class="table-responsive-ag">
                            <table class="table table-ag align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Référence</th>
                                        <th>Désignation</th>
                                        <th class="text-center">Qté Commandée</th>
                                        <th class="text-center">Déjà Reçu</th>
                                        <th class="text-center bg-success-subtle" width="150">Reçu ce jour</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($lignes as $l): ?>
                                    <?php 
                                        $reste = max(0, $l['qte_commandee'] - $l['qte_recue']);
                                        $style_row = ($reste == 0) ? 'opacity: 0.5;' : ''; 
                                    ?>
                                    <tr style="<?= $style_row ?>">
                                        <td class="font-monospace small"><?= h($l['ref_fournisseur']) ?></td>
                                        <td>
                                            <?= h($l['designation_commerciale'] ?? $l['designation']) ?>
                                            <div class="small text-muted"><?= h($l['nom_couleur']) ?></div>
                                        </td>
                                        <td class="text-center fw-bold"><?= $l['qte_commandee'] + 0 ?></td>
                                        <td class="text-center text-muted"><?= $l['qte_recue'] + 0 ?></td>
                                        <td>
                                            <?php if($reste > 0): ?>
                                                <input type="number" step="0.01" min="0" max="<?= $reste ?>" 
                                                       name="qte_recue[<?= $l['id'] ?>]" 
                                                       class="form-control text-center fw-bold border-success" 
                                                       value="<?= $reste ?>">
                                            <?php else: ?>
                                                <div class="text-center text-success"><i class="fas fa-check"></i> Soldé</div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-petrol btn-lg shadow rounded-pill">
                                <i class="fas fa-check-circle me-2"></i> Valider la Réception
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</body>
</html>

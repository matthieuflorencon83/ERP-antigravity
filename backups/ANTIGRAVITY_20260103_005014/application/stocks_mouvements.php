<?php
// stocks_mouvements.php - Gestion des Entrées/Sorties Manuelles
session_start();
require_once 'db.php';
require_once 'functions.php';

// I. TRAITEMENT FORMULAIRE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $type = $_POST['type_mouvement']; // ENTREE, SORTIE, INVENTAIRE
    $article_id = (int)$_POST['article_id'];
    $finition_id = !empty($_POST['finition_id']) ? (int)$_POST['finition_id'] : null;
    $qte = (float)$_POST['quantite'];
    $commentaire = trim($_POST['commentaire']);
    $user_id = $_SESSION['user_id'] ?? 1;

    if ($article_id && $qte > 0) {
        try {
            $pdo->beginTransaction();

            // A. Enregistrement Mouvement (Table Unifiée 'stocks_mouvements')
            $sql = "INSERT INTO stocks_mouvements (article_id, finition_id, user_id, type_mouvement, quantite, date_mouvement, commentaire) 
                    VALUES (?, ?, ?, ?, ?, NOW(), ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$article_id, $finition_id, $user_id, $type, $qte, $commentaire]);

            // B. Mise à jour du Stock Réel (Table 'stocks')
            // Logique UPSERT pour gérer si la ligne de stock n'existe pas encore
            
            // Calcul du signe
            $qte_signee = ($type === 'SORTIE') ? -$qte : $qte;

            // On essaie d'abord de mettre à jour
            $sql_check = "SELECT id FROM stocks WHERE article_id = ? AND (finition_id = ? OR (finition_id IS NULL AND ? IS NULL)) AND emplacement = 'Atelier'";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$article_id, $finition_id, $finition_id]);
            $stock_exist = $stmt_check->fetch();

            if ($stock_exist) {
                // Update
                $sql_up = "UPDATE stocks SET quantite = quantite + ?, date_derniere_maj = NOW() WHERE id = ?";
                $pdo->prepare($sql_up)->execute([$qte_signee, $stock_exist['id']]);
            } else {
                // Insert (Seulement si positif ou si on accepte le negatif)
                $sql_in = "INSERT INTO stocks (article_id, finition_id, quantite, emplacement, date_derniere_maj) VALUES (?, ?, ?, 'Atelier', NOW())";
                $pdo->prepare($sql_in)->execute([$article_id, $finition_id, $qte_signee]);
            }

            $pdo->commit();
            $message = "<div class='alert alert-success'>Mouvement enregistré avec succès !</div>";

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
        }
    }
}

// II. CHARGEMENT DONNEES
$articles = $pdo->query("SELECT id, designation, reference_interne FROM articles ORDER BY designation")->fetchAll();
$finitions = $pdo->query("SELECT id, nom_couleur FROM finitions ORDER BY nom_couleur")->fetchAll();

// Mouvements récents
$mouvements = $pdo->query("
    SELECT sm.*, a.designation, u.nom_complet as user_nom 
    FROM stocks_mouvements sm
    JOIN articles a ON sm.article_id = a.id
    LEFT JOIN utilisateurs u ON sm.user_id = u.id
    ORDER BY sm.date_mouvement DESC 
    LIMIT 20
")->fetchAll();

$page_title = 'Mouvements de Stock';
require_once 'header.php';
?>

<div class="main-content">
    <?= $message ?? '' ?>

    <div class="row">
        <!-- FORMULAIRE RAPIDE -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="fas fa-exchange-alt me-2"></i> Saisie Mouvement
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="type_mouvement" class="form-select fw-bold">
                                <option value="ENTREE" class="text-success">✅ Entrée (Achat/Retour)</option>
                                <option value="SORTIE" class="text-danger">❌ Sortie (Prod/Casse)</option>
                                <option value="INVENTAIRE" class="text-primary">⚖️ Inventaire (Ajustement)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Article</label>
                            <select name="article_id" class="form-select select2-enable" required>
                                <option value="">-- Choisir --</option>
                                <?php foreach($articles as $a): ?>
                                    <option value="<?= $a['id'] ?>"><?= $a['reference_interne'] ?> - <?= $a['designation'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Finition (Optionnel)</label>
                            <select name="finition_id" class="form-select">
                                <option value="">Brut / Standard</option>
                                <?php foreach($finitions as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= $f['nom_couleur'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Quantité</label>
                            <input type="number" step="0.01" name="quantite" class="form-control font-monospace fs-4" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Commentaire</label>
                            <textarea name="commentaire" class="form-control" rows="2" placeholder="Ex: Commande reçue partielle..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 py-2">Valider Mouvement</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- LISTE HISTORIQUE -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Historique Récent</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Article</th>
                                <th class="text-end">Qté</th>
                                <th>Par</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($mouvements as $mv): ?>
                                <tr>
                                    <td class="small text-muted"><?= date('d/m H:i', strtotime($mv['date_mouvement'])) ?></td>
                                    <td>
                                        <?php if($mv['type_mouvement'] == 'ENTREE'): ?>
                                            <span class="badge bg-success-subtle text-success">ENTRÉE</span>
                                        <?php elseif($mv['type_mouvement'] == 'SORTIE'): ?>
                                            <span class="badge bg-danger-subtle text-danger">SORTIE</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary-subtle text-primary">AJUST.</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($mv['designation']) ?></td>
                                    <td class="text-end fw-bold font-monospace"><?= $mv['quantite'] ?></td>
                                    <td class="small">
                                        <i class="fas fa-user-circle text-muted me-1"></i><?= $mv['user_nom'] ?: 'Système' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Select2 Init Simple -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2-enable').select2({ width: '100%' });
    });
</script>
</body>
</html>

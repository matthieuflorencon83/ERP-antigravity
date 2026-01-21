<?php
/**
 * commandes_saisie.php
 * Module Achats : Création d'une nouvelle commande fournisseur (Header)
 */

require_once 'auth.php';
// session_start(); // Remplacé par auth.php qui gère la session
require_once 'db.php';
require_once 'functions.php';

// Force UTF-8
header('Content-Type: text/html; charset=utf-8');

// TRAITEMENT DU FORMULAIRE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $fournisseur_id = (int)$_POST['fournisseur_id'];
    $affaire_id = !empty($_POST['affaire_id']) ? (int)$_POST['affaire_id'] : null;
    $date_commande = $_POST['date_commande'];
    $designation = trim($_POST['designation'] ?? '');

    // Logic for Lieu de Livraison
    $lieu_select = $_POST['lieu_livraison_select'];
    if ($lieu_select === 'Autre') {
        $lieu_livraison = trim($_POST['lieu_livraison_autre']);
        if (empty($lieu_livraison)) $lieu_livraison = 'Autre (Non précisé)';
    } else {
        $lieu_livraison = $lieu_select;
    }
    
    // Génération Référence Interne (Format: CMD-YYYY-ID)
    // Astuce : On insert puis on update la ref, ou on estimate l'id
    // Ici on va faire simple : CMD-TIMESTAMP pour l'instant ou CMD-UNK
    
    try {
        $stmt = $pdo->prepare("INSERT INTO commandes_achats (fournisseur_id, affaire_id, date_commande, lieu_livraison, statut, ref_interne, designation) VALUES (?, ?, ?, ?, 'Brouillon', 'TEMP', ?)");
        $stmt->execute([$fournisseur_id, $affaire_id, $date_commande, $lieu_livraison, $designation]);
        $new_id = $pdo->lastInsertId();
        
        // Update Ref Propre
        $ref_clean = "CMD-" . date('Y') . "-" . str_pad($new_id, 4, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE commandes_achats SET ref_interne = ? WHERE id = ?")->execute([$ref_clean, $new_id]);
        
        // Redirection vers le détail pour ajouter des lignes
        header("Location: commandes_detail.php?id=" . $new_id);
        exit;
        
    } catch (Exception $e) {
        $error = "Erreur création : " . $e->getMessage();
    }
}

// RECUPERATION DONNEES POUR LISTES DEROULANTES
try {
    $fournisseurs = $pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom ASC")->fetchAll();
    $affaires = $pdo->query("SELECT id, nom_affaire, numero_prodevis FROM affaires WHERE statut != 'Clôturé' ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {
    die("Erreur SQL : " . $e->getMessage());
}

$page_title = 'Nouvelle Commande';
require_once 'header.php';
?>

<div class="main-content">
    <div class="container-fluid mt-4">
        
        <!-- Breadcrumb / Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="commandes_liste.php" class="text-secondary text-decoration-none">Commandes</a></li>
                        <li class="breadcrumb-item active text-primary" aria-current="page">Nouvelle</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-file-invoice me-2"></i>Informations Commande</h5>
                    </div>
                    <div class="card-body p-4">
                        
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger d-flex align-items-center shadow-sm" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div><?= $error ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <?= csrf_field() ?>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Fournisseur <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-truck"></i></span>
                                    <select name="fournisseur_id" class="form-select" required>
                                        <option value="" selected disabled>Choisir un fournisseur...</option>
                                        <?php foreach($fournisseurs as $f): ?>
                                            <option value="<?= $f['id'] ?>"><?= h($f['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Affaire liée (Chantier)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-hard-hat"></i></span>
                                    <select name="affaire_id" class="form-select">
                                        <option value="">-- Commande de Stock (Aucune affaire) --</option>
                                        <?php foreach($affaires as $a): ?>
                                            <option value="<?= $a['id'] ?>">
                                                <?= h($a['numero_prodevis']) ?> - <?= h($a['nom_affaire']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-text mt-1 text-muted"><small>Si laissé vide, la commande sera considérée comme du Stock.</small></div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Désignation (Optionnel)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-tag"></i></span>
                                    <input type="text" name="designation" class="form-control" placeholder="Ex: Menuiseries Alu rez-de-chaussée">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Date de Commande</label>
                                    <input type="date" name="date_commande" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <select name="lieu_livraison_select" id="lieu_livraison_select" class="form-select" onchange="toggleLieuAutre()">
                                        <option value="Atelier (Arts Alu)">Atelier (Arts Alu)</option>
                                        <option value="Chantier Client">Chantier Client</option>
                                        <option value="Autre">Autre (Saisie manuelle)</option>
                                    </select>
                                    <input type="text" name="lieu_livraison_autre" id="lieu_livraison_autre" class="form-control mt-2 d-none" placeholder="Précisez le lieu..." value="">
                                </div>
                            </div>

                            <script>
                                function toggleLieuAutre() {
                                    const select = document.getElementById('lieu_livraison_select');
                                    const input = document.getElementById('lieu_livraison_autre');
                                    if(select.value === 'Autre') {
                                        input.classList.remove('d-none');
                                        input.required = true;
                                        input.focus();
                                    } else {
                                        input.classList.add('d-none');
                                        input.required = false;
                                        input.value = ''; // Reset
                                    }
                                }
                            </script>

                            <div class="d-flex justify-content-between align-items-center mt-5 pt-3 border-top">
                                <a href="commandes_liste.php" class="btn btn-outline-secondary rounded-pill px-4">
                                    <i class="fas fa-arrow-left me-2"></i>Annuler
                                </a>
                                <button type="submit" class="btn btn-petrol rounded-pill px-4 shadow-sm">
                                    <i class="fas fa-check me-2"></i>Créer la commande
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


</body>
</html>

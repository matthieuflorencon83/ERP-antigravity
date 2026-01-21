<?php
/**
 * affaires_modifier.php
 * Modification d'une affaire existante
 */

require 'db.php';
require 'functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header("Location: affaires_liste.php");
    exit;
}

// Récupération de l'affaire
$stmt = $pdo->prepare("SELECT * FROM affaires WHERE id = ?");
$stmt->execute([$id]);
$affaire = $stmt->fetch();

if (!$affaire) {
    die("Affaire introuvable.");
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $client_id = (int)$_POST['client_id'];
    $nom_affaire = trim($_POST['nom_affaire']);
    $numero_prodevis = trim($_POST['numero_prodevis']);
    $statut = $_POST['statut'];
    $designation = trim($_POST['designation'] ?? '');

    if ($client_id && $nom_affaire) {
        try {
            $stmt = $pdo->prepare("UPDATE affaires SET client_id = ?, nom_affaire = ?, numero_prodevis = ?, statut = ?, designation = ? WHERE id = ?");
            $stmt->execute([$client_id, $nom_affaire, $numero_prodevis, $statut, $designation, $id]);
            
            header("Location: affaires_liste.php?success=updated");
            exit;
        } catch (PDOException $e) {
            $error = "Erreur technique : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Récupération des clients pour le select
$clients = $pdo->query("SELECT id, nom_principal FROM clients ORDER BY nom_principal ASC")->fetchAll();

$page_title = 'Modifier Affaire #' . $affaire['numero_prodevis'];
require_once 'header.php';
?>

<div class="main-content">
    <div class="container-fluid mt-4">
        
        <!-- Breadcrumb / Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="affaires_liste.php" class="text-secondary text-decoration-none">Mes Affaires</a></li>
                        <li class="breadcrumb-item active text-primary" aria-current="page">Modifier</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-warning text-dark py-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Modifier Affaire</h5>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger d-flex align-items-center shadow-sm" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div><?= htmlspecialchars($error) ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <?= csrf_field() ?>
                            
                            <!-- INFO CLIENT -->
                            <div class="mb-4">
                                <label for="client_id" class="form-label fw-bold">Client <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-user"></i></span>
                                    <select name="client_id" id="client_id" class="form-select" required>
                                        <option value="">-- Sélectionner un client --</option>
                                        <?php foreach ($clients as $client): ?>
                                            <option value="<?= $client['id'] ?>" <?= $client['id'] == $affaire['client_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($client['nom_principal']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- NOM ET REF -->
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="numero_prodevis" class="form-label fw-bold">N° Devis / Réf</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-hashtag"></i></span>
                                        <input type="text" name="numero_prodevis" id="numero_prodevis" class="form-control" value="<?= htmlspecialchars($affaire['numero_prodevis']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="statut" class="form-label fw-bold">Statut</label>
                                    <select name="statut" id="statut" class="form-select">
                                        <?php 
                                        $statuts = ['Devis', 'Signé', 'Clôturé', 'Annulé'];
                                        foreach($statuts as $s): ?>
                                            <option value="<?= $s ?>" <?= $affaire['statut'] == $s ? 'selected' : '' ?>><?= $s ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="nom_affaire" class="form-label fw-bold">Nom du Chantier <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-sign"></i></span>
                                    <input type="text" name="nom_affaire" id="nom_affaire" class="form-control" value="<?= htmlspecialchars($affaire['nom_affaire']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="designation" class="form-label fw-bold">Désignation / Notes</label>
                                <textarea name="designation" id="designation" class="form-control" rows="3"><?= htmlspecialchars($affaire['designation'] ?? '') ?></textarea>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-5 pt-3 border-top">
                                <a href="affaires_liste.php" class="btn btn-outline-secondary rounded-pill px-4">
                                    <i class="fas fa-arrow-left me-2"></i>Annuler
                                </a>
                                <button type="submit" class="btn btn-warning text-dark rounded-pill px-4 shadow-sm fw-bold">
                                    <i class="fas fa-save me-2"></i>Enregistrer
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

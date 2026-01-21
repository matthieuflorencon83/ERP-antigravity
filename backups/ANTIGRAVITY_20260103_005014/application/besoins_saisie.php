<?php
/**
 * besoins_saisie.php
 * Module Technique : Saisie des besoins (Débit) pour une affaire.
 * 
 * @project Antigravity
 * @version 1.0
 */

require 'db.php';
require 'functions.php';

// Force UTF-8
header('Content-Type: text/html; charset=utf-8');

$message = "";
$error = "";

// Récupération ID Affaire
$affaire_id = isset($_GET['affaire_id']) ? (int)$_GET['affaire_id'] : 0;

// Traitement du Formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $affaire_id = (int)$_POST['affaire_id'];
    $modele_id = (int)$_POST['modele_id'];
    $finition_id = (int)$_POST['finition_id'];
    $longueur = (int)$_POST['longueur_mm'];
    $quantite = (int)$_POST['quantite'];
    
    if ($affaire_id && $modele_id && $longueur > 0 && $quantite > 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO besoins_chantier (affaire_id, modele_profil_id, finition_id, longueur_mm, quantite, statut)
                VALUES (?, ?, ?, ?, ?, 'A_CALCULER')
            ");
            $stmt->execute([$affaire_id, $modele_id, $finition_id ?: null, $longueur, $quantite]);
            
            // Redirection vers le cockpit
            header("Location: affaires_detail.php?id=$affaire_id&msg=besoin_ok#besoins");
            exit;
        } catch (Exception $e) {
            $error = "Erreur SQL : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Chargement des données pour le formulaire
try {
    // Info Affaire (pour le fil d'ariane)
    if ($affaire_id) {
        $stmt = $pdo->prepare("SELECT nom_affaire FROM affaires WHERE id = ?");
        $stmt->execute([$affaire_id]);
        $affaire = $stmt->fetch();
        if (!$affaire) die("Affaire introuvable.");
    }

    // Listes déroulantes
    $modeles = $pdo->query("SELECT id, designation_interne FROM modeles_profils ORDER BY designation_interne")->fetchAll();
$finitions = $pdo->query("SELECT id, nom_couleur, code_ral FROM finitions ORDER BY nom_couleur")->fetchAll();

} catch (Exception $e) {
    die("Erreur de chargement : " . $e->getMessage());
}

$page_title = 'Saisie de Débit';
require_once 'header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="index.php" class="text-secondary">Accueil</a></li>
                        <?php if ($affaire_id): ?>
                            <li class="breadcrumb-item"><a href="affaires_detail.php?id=<?= $affaire_id ?>" class="text-secondary">Affaire <?= htmlspecialchars($affaire['nom_affaire']) ?></a></li>
                        <?php endif; ?>
                        <li class="breadcrumb-item active text-primary" aria-current="page">Saisie Débit</li>
                    </ol>
                </nav>
            </div>
            <div>
                 <a href="affaires_detail.php?id=<?= $affaire_id ?>#besoins" class="btn btn-outline-dark rounded-pill">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-pen me-2"></i>Ajout manuel de besoins matière</h5>
                    </div>
                    <div class="card-body p-4">
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger shadow-sm border-0"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="affaire_id" value="<?= $affaire_id ?>">

                            <div class="row g-4">
                                
                                <!-- 1. LE PROFIL -->
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Modèle / Profil <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-lg shadow-sm border-primary-subtle" name="modele_id" required autofocus>
                                        <option value="">-- Sélectionner un profil --</option>
                                        <?php foreach($modeles as $m): ?>
                                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['designation_interne']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text"><i class="fas fa-info-circle me-1"></i>Ce que vous voulez fabriquer ou couper.</div>
                                </div>

                                <!-- 2. LA FINITION -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Finition / Couleur</label>
                                    <select class="form-select" name="finition_id">
                                        <option value="">Brut / Standard</option>
                                        <?php foreach($finitions as $f): ?>
                                            <option value="<?= $f['id'] ?>">
                                                <?= htmlspecialchars($f['nom_couleur']) ?> 
                                                <?= $f['code_ral'] ? '('.$f['code_ral'].')' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- 3. DIMENSIONS -->
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Longueur (mm) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control fw-bold" name="longueur_mm" placeholder="ex: 2500" min="1" required>
                                        <span class="input-group-text">mm</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Quantité <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control fw-bold text-center" name="quantite" value="1" min="1" required>
                                </div>

                                <!-- BOUTONS -->
                                <div class="col-12 mt-4 text-end">
                                    <button type="submit" class="btn btn-petrol btn-lg shadow rounded-pill px-5">
                                        <i class="fas fa-plus-circle me-2"></i> Ajouter au besoin
                                    </button>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Aide Saisie Rapide (Simulation) -->
                <div class="alert alert-light mt-4 border shadow-sm d-flex align-items-center">
                    <div class="bg-primary text-white rounded-circle p-3 me-3">
                        <i class="fas fa-keyboard fa-lg"></i>
                    </div>
                    <div>
                        <strong class="text-primary">Astuce :</strong>
                        La saisie en masse sera bientôt disponible via un import Excel ou une grille éditable.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


</body>
</html>

<?php
/**
 * affaires_nouveau.php
 * Création d'une nouvelle affaire
 */

require 'db.php';
require 'functions.php';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $client_id = (int)$_POST['client_id'];
    $nom_affaire = trim($_POST['nom_affaire']);
    $numero_prodevis = trim($_POST['numero_prodevis']);
    $statut = 'Devis'; // Défaut
    $date_creation = date('Y-m-d H:i:s');

    if ($client_id && $nom_affaire) {
        // Auto-generate ref if empty to avoid Unique Constraint on ''
        if (empty($numero_prodevis)) {
            $numero_prodevis = 'AFF-' . date('Ymd-His');
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO affaires (client_id, nom_affaire, numero_prodevis, statut, date_creation) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$client_id, $nom_affaire, $numero_prodevis, $statut, $date_creation]);
            $new_id = $pdo->lastInsertId();
            
            header("Location: affaires_detail.php?id=$new_id&success=created");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Ce numéro de devis/référence existe déjà. Veuillez en choisir un autre.";
            } else {
                $error = "Erreur technique : " . $e->getMessage();
            }
        }
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Récupération des clients pour le select
$clients = $pdo->query("SELECT id, nom_principal FROM clients ORDER BY nom_principal ASC")->fetchAll();

$page_title = 'Nouvelle Affaire';
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
                        <li class="breadcrumb-item active text-primary" aria-current="page">Nouveau</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-file-contract me-2"></i>Informations Générales</h5>
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
                            <div class="mb-4">
                                <label for="client_id" class="form-label fw-bold">Client <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-user"></i></span>
                                    <select name="client_id" id="client_id" class="form-select" required>
                                        <option value="">-- Sélectionner un client --</option>
                                        <?php foreach ($clients as $client): ?>
                                            <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['nom_principal']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-text mt-2">
                                    <a href="clients_liste.php" class="text-decoration-none text-info">
                                        <i class="fas fa-user-plus me-1"></i>Client introuvable ? Gérer les clients
                                    </a>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="nom_affaire" class="form-label fw-bold">Nom du Chantier / Affaire <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-sign"></i></span>
                                    <input type="text" name="nom_affaire" id="nom_affaire" class="form-control" placeholder="Ex: Rénovation Mr Martin" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="numero_prodevis" class="form-label fw-bold">Numéro ProDevis / Réf</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-hashtag"></i></span>
                                    <input type="text" name="numero_prodevis" id="numero_prodevis" class="form-control" placeholder="Ex: 2501001">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-5 pt-3 border-top">
                                <a href="affaires_liste.php" class="btn btn-outline-secondary rounded-pill px-4">
                                    <i class="fas fa-arrow-left me-2"></i>Annuler
                                </a>
                                <button type="submit" class="btn btn-petrol rounded-pill px-4 shadow-sm">
                                    <i class="fas fa-check me-2"></i>Créer l'Affaire
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

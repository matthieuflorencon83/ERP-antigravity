<?php
// parametres.php - Gestion complète (Dossiers + Système)
require_once 'auth.php'; // Securite
require_once 'functions.php';

require_once 'db.php';

// Sécurité Admin
if (($_SESSION['user_role'] ?? '') !== 'ADMIN') { 
    die("Accès refusé. Réservé aux administrateurs.");
}

$message = "";

// 1. TRAITEMENT DE LA SAUVEGARDE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    foreach ($_POST as $cle => $valeur) {
        // On ne touche pas au token de sécurité s'il y en a
        if ($cle == 'csrf_token') continue;

        $valeur_propre = trim($valeur);

        // Si c'est un chemin (contient 'chemin_'), on normalise les slashs
        if (strpos($cle, 'chemin_') !== false) {
            $valeur_propre = str_replace('\\', '/', $valeur_propre);
            if (!empty($valeur_propre) && substr($valeur_propre, -1) != '/') {
                $valeur_propre .= '/';
            }
        }

        // Mise à jour SQL
        $stmt = $pdo->prepare("UPDATE parametres_generaux SET valeur_config = ? WHERE cle_config = ?");
        $stmt->execute([$valeur_propre, $cle]);
    }
    $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Paramètres enregistrés avec succès !</div>';
}

// 2. RÉCUPÉRATION DES PARAMÈTRES PAR CATÉGORIE
// Groupe 1 : Les Dossiers
$params_chemins = $pdo->query("SELECT * FROM parametres_generaux WHERE cle_config LIKE 'chemin_%' ORDER BY cle_config")->fetchAll();

// Groupe 2 : La Technique (IA, Email, etc.) - Tout ce qui n'est pas un chemin
$params_tech = $pdo->query("SELECT * FROM parametres_generaux WHERE cle_config NOT LIKE 'chemin_%' ORDER BY cle_config")->fetchAll();

$page_title = 'Paramètres';
require_once 'header.php';
?>

<div class="main-content">

    
    <?= $message ?>

    <form method="POST">
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-folder-tree me-2"></i>Connexions Dossiers (Dropbox)</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle"></i> Ces chemins permettent au logiciel de lire et ranger vos PDF.
                        </div>

                        <?php foreach($params_chemins as $row): ?>
                            <?php 
                                $chemin = $row['valeur_config'];
                                $existe = (!empty($chemin) && is_dir($chemin));
                                $status_icon = $existe ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-exclamation-triangle text-danger"></i>';
                            ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted"><?= htmlspecialchars($row['description']) ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><?= $status_icon ?></span>
                                    <input type="text" class="form-control font-monospace" name="<?= $row['cle_config'] ?>" 
                                           value="<?= htmlspecialchars($chemin) ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if(empty($params_chemins)): ?>
                            <p class="text-muted text-center py-3">Aucun chemin configuré.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-microchip me-2"></i>Intelligence Artificielle & Système</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach($params_tech as $row): ?>
                            <?php 
                                $is_password = (strpos($row['cle_config'], 'password') !== false || strpos($row['cle_config'], 'key') !== false);
                                $input_type = $is_password ? 'password' : 'text';
                                $icon = (strpos($row['cle_config'], 'api') !== false) ? 'fa-robot' : 'fa-network-wired';
                            ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted"><?= htmlspecialchars($row['description']) ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas <?= $icon ?>"></i></span>
                                    <input type="<?= $input_type ?>" class="form-control" name="<?= $row['cle_config'] ?>" 
                                           value="<?= htmlspecialchars($row['valeur_config']) ?>" 
                                           placeholder="Non configuré">
                                </div>
                                <?php if($is_password): ?>
                                    <div class="form-text text-muted small">Donnée sensible masquée par sécurité.</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if(empty($params_tech)): ?>
                            <p class="text-muted text-center py-3">Aucun paramètre système.</p>
                        <?php endif; ?>

                        <hr class="my-3">
                        <div class="d-grid">
                            <a href="admin_logs.php" class="btn btn-outline-primary shadow-sm hover-scale">
                                <i class="fas fa-user-clock me-2"></i> Voir l'Historique des Connexions
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mt-4">
            <button type="submit" class="btn btn-petrol btn-lg px-5 shadow rounded-pill">
                <i class="fas fa-save me-2"></i> Enregistrer les Paramètres
            </button>
        </div>
    </form>
</div>


</body>
</html>

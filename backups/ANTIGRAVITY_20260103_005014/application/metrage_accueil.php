<?php
// metrage_accueil.php - Portail Assistant Métrage (Mobile First)
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$page_title = 'Assistant Métrage';

// TRAITEMENT CREATION RAPIDE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'start_new') {
    $affaire_id = (int)$_POST['affaire_id'];
    
    // Vérif si existe déjà
    $exist = $pdo->query("SELECT id FROM metrage_interventions WHERE affaire_id = $affaire_id")->fetchColumn();
    if ($exist) {
        $mission_id = $exist;
    } else {
        // Création
        $stmt = $pdo->prepare("INSERT INTO metrage_interventions (affaire_id, date_prevue, statut, notes_generales) VALUES (?, NOW(), 'EN_COURS', 'Métrage spontané via Assistant')");
        $stmt->execute([$affaire_id]);
        $mission_id = $pdo->lastInsertId();
    }
    
    // Redirection vers l'interface terrain
    header("Location: gestion_metrage_terrain.php?id=$mission_id");
    exit;
}

// RECUPERATION AFFAIRES (TOUTES : Signé, Devis, Brouillon...)
// On exclut celles qui ont déjà un métrage terminé pour ne pas polluer, ou on met tout.
// On va mettre les 50 dernières actives.
$sql_affaires = "
    SELECT a.id, a.nom_affaire, c.nom_principal, a.numero_prodevis, a.statut
    FROM affaires a
    JOIN clients c ON a.client_id = c.id
    WHERE a.statut NOT IN ('Clôturé', 'Annulé')
    ORDER BY a.date_creation DESC
    LIMIT 100
";
$affaires = $pdo->query($sql_affaires)->fetchAll();

require_once 'header.php';
?>

<div class="main-content">
    <div class="container mt-4">
        
        <div class="text-center mb-5">
            <h1 class="fw-bold"><i class="fas fa-ruler-combined text-primary me-2"></i>Assistant Métrage</h1>
            <p class="text-muted">Créez des relevés techniques pour n'importe quel dossier, signé ou non.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                
                <!-- CARTE DE DEMARRAGE -->
                <div class="card shadow-lg border-0 mb-4 bg-primary text-white" style="border-radius: 20px;">
                    <div class="card-body p-4 text-center">
                        <i class="fas fa-play-circle fa-4x mb-3 text-white opacity-75"></i>
                        <h3 class="fw-bold">Nouveau Métrage</h3>
                        <p class="opacity-75 mb-4">Sélectionnez une affaire pour démarrer immédiatemment.</p>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="start_new">
                            
                            <div class="input-group input-group-lg mb-3">
                                <span class="input-group-text bg-white border-0 text-primary"><i class="fas fa-search"></i></span>
                                <select name="affaire_id" class="form-select border-0 shadow-none" required style="border-radius: 0 10px 10px 0;">
                                    <option value="" selected disabled>Choisir un dossier...</option>
                                    <?php foreach($affaires as $a): ?>
                                        <option value="<?= $a['id'] ?>">
                                            <?= h($a['nom_principal']) ?> - <?= h($a['nom_affaire']) ?> (<?= $a['statut'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-light text-primary fw-bold w-100 py-3 rounded-pill shadow-sm">
                                <i class="fas fa-bolt me-2"></i>LANCER LE MÉTRAGE
                            </button>
                        </form>
                    </div>
                </div>

                <!-- RACCOURCIS -->
                <div class="d-grid gap-3">
                    <a href="gestion_metrage_terrain.php" class="btn btn-outline-dark btn-lg py-3 rounded-4 text-start px-4">
                        <i class="fas fa-list-ul me-3 fa-lg"></i>
                        Voir mes métrages en cours
                    </a>
                    
                     <a href="gestion_metrage_planning.php" class="btn btn-outline-secondary py-3 rounded-4 text-start px-4">
                        <i class="fas fa-calendar-alt me-3 fa-lg"></i>
                        Accéder au Planning complet (Bureau)
                    </a>
                </div>

            </div>
        </div>
        
    </div>
</div>

<?php require_once 'footer.php'; ?>

<?php
// metrage_rapport.php
// Affiche le rapport complet d'une mission de métrage
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$id = $_GET['id'] ?? 0;
$mission = $pdo->query("SELECT m.*, a.nom_affaire, a.numero_prodevis, c.nom_principal, c.ville 
                        FROM metrage_interventions m 
                        JOIN affaires a ON m.affaire_id = a.id
                        JOIN clients c ON a.client_id = c.id
                        WHERE m.id = $id")->fetch();

if (!$mission) die("Mission introuvable.");

$lignes = $pdo->query("SELECT l.*, t.nom as type_nom, t.icone 
                       FROM metrage_lignes l 
                       JOIN metrage_types t ON l.metrage_type_id = t.id
                       WHERE intervention_id = $id")->fetchAll();

$page_title = "Rapport Métrage #" . $mission['id'];
require_once 'header.php';
?>

<div class="main-content">
    <div class="container-fluid mt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="gestion_metrage_planning.php" class="text-muted"><i class="fas fa-arrow-left me-2"></i>Retour Planning</a>
                <h2 class="fw-bold mt-2">Rapport Technique</h2>
                <span class="badge bg-dark"><?= h($mission['nom_affaire']) ?></span>
                <span class="badge bg-secondary"><?= h($mission['nom_principal']) ?></span>
            </div>
            <div>
                <button class="btn btn-outline-primary me-2"><i class="fas fa-print me-2"></i>Imprimer</button>
                <a href="affaires_generer_commandes.php?metrage_id=<?= $id ?>" class="btn btn-success rounded-pill">
                    <i class="fas fa-shopping-cart me-2"></i>Générer Commande Fournisseur
                </a>
            </div>
        </div>

        <div class="row">
            <?php foreach($lignes as $l): ?>
            <?php $data = json_decode($l['donnees_json'], true); ?>
            <div class="col-12 mb-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="me-3 text-center" style="width: 50px;">
                                <i class="<?= $l['icone'] ?> fa-2x text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="fw-bold"><?= h($l['type_nom']) ?> <small class="text-muted ms-2">(<?= h($l['localisation']) ?>)</small></h5>
                                
                                <div class="row mt-3">
                                    <?php if($data): ?>
                                        <?php foreach($data as $pt_id => $val): ?>
                                            <?php 
                                            // Récupérer label via une petite requête ou cache (ici simplifié pour affichage rapide)
                                            // Idéalement on aurait join les labels, mais le JSON stocke ID => Value.
                                            // Pour le rapport pro, on ferait un mapping. Ici on affiche brut pour le test.
                                            // UPDATE : Récupérer le label est mieux.
                                            $label_q = $pdo->query("SELECT label FROM metrage_points_controle WHERE id=$pt_id")->fetchColumn();
                                            ?>
                                            <div class="col-md-4 mb-2">
                                                <small class="text-muted d-block"><?= h($label_q ?? 'Point #'.$pt_id) ?></small>
                                                <strong><?= h($val) ?></strong>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <?php if(!empty($l['notes_observateur'])): ?>
                                    <div class="alert alert-light mt-2 mb-0">
                                        <i class="fas fa-comment-alt me-2 text-warning"></i>
                                        <?= h($l['notes_observateur']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php require_once 'footer.php'; ?>

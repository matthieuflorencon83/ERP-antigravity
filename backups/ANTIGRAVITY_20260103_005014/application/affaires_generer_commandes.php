<?php
// affaires_generer_commandes.php
// WIZARD MULTI-COMMANDES : Permet de sélectionner des lignes de métrage ou besoin et de générer UNE commande spécifique.

require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$affaire_id = isset($_REQUEST['affaire_id']) ? (int)$_REQUEST['affaire_id'] : 0;
$metrage_id = isset($_REQUEST['metrage_id']) ? (int)$_REQUEST['metrage_id'] : 0;

// Si metrage_id donné, on retrouve l'affaire
if ($affaire_id === 0 && $metrage_id > 0) {
    $m = $pdo->query("SELECT affaire_id FROM metrage_interventions WHERE id=$metrage_id")->fetch();
    if ($m) $affaire_id = $m['affaire_id'];
}

if ($affaire_id === 0) die("ID Affaire manquant.");

// Récuperation Infos Affaire
$affaire = $pdo->query("SELECT a.*, c.nom_principal FROM affaires a JOIN clients c ON a.client_id = c.id WHERE a.id=$affaire_id")->fetch();

// -----------------------------------------------------------------------------
// TRAITEMENT POST : GÉNÉRATION COMMANDE
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['items'])) {
    try {
        $pdo->beginTransaction();
        
        $fournisseur_id = (int)$_POST['fournisseur_id'];
        $mode_commande = $_POST['mode_commande']; // EMAIL, PORTAIL_WEB, TELEPHONE
        $designation_cmd = $_POST['designation_cmd']; // Ex: "Menuiserie K-LINE"
        $items = $_POST['items']; // Array of "type_id" strings (ex: "M_12" for Metrage Line 12, "B_5" for Besoin 5)
        
        // 1. Création Commande
        $ref_interne = "CMD-" . date('ymd') . "-" . rand(100,999);
        $stmt = $pdo->prepare("INSERT INTO commandes_achats (fournisseur_id, affaire_id, ref_interne, designation, date_commande, statut, mode_commande, source_donnees) VALUES (?, ?, ?, ?, CURDATE(), 'Brouillon', ?, 'MANUEL')");
        $stmt->execute([$fournisseur_id, $affaire_id, $ref_interne, $designation_cmd, $mode_commande]);
        $cmd_id = $pdo->lastInsertId();
        
        // 2. Traitement des Lignes
        $stmtLigne = $pdo->prepare("INSERT INTO lignes_achat (commande_id, designation, qte_commandee, prix_unitaire_achat, metrage_ligne_id) VALUES (?, ?, ?, 0, ?)");
        
        foreach ($items as $item_code) {
            $parts = explode('_', $item_code);
            $type = $parts[0]; // M=Metrage, B=Besoin
            $id = (int)$parts[1];
            
            if ($type === 'M') {
                // Ligne Métrage
                $line = $pdo->query("SELECT l.*, t.nom as type_nom FROM metrage_lignes l JOIN metrage_types t ON l.metrage_type_id=t.id WHERE l.id=$id")->fetch();
                $json = json_decode($line['donnees_json'], true);
                
                // Construction Designation
                $desc = $line['type_nom'] . " (" . $line['localisation'] . ")";
                // Ajout détails clés (Dimensions)
                foreach($json as $k => $v) {
                    if (stripos($v, 'mm') !== false || is_numeric($v)) $desc .= " - $v"; 
                }
                
                $stmtLigne->execute([$cmd_id, $desc, 1, $id]); // HARD LINK: metrage_ligne_id = $id
                
                // Update Statut Ligne Métrage
                $pdo->exec("UPDATE metrage_lignes SET statut_traitement='TRAITE' WHERE id=$id");
                
            } elseif ($type === 'B') {
                // Besoin Chantier (Legacy)
                $besoin = $pdo->query("SELECT * FROM besoins_chantier WHERE id=$id")->fetch();
                $stmtLigne->execute([$cmd_id, $besoin['designation_interne'], $besoin['quantite'], null]); // Null FK for Besoin
                
                // Update Besoin
                $pdo->exec("UPDATE besoins_chantier SET statut='COMMANDE' WHERE id=$id");
            }
        }
        
        $pdo->commit();
        header("Location: affaires_detail.php?id=$affaire_id&success=cmd_created&cmd_id=$cmd_id#commandes");
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// -----------------------------------------------------------------------------
// RÉCUPÉRATION DES ÉLÉMENTS "A COMMANDER"
// -----------------------------------------------------------------------------
// 1. Lignes de Métrage (Non Traitées)
$sqlM = "SELECT l.*, t.nom as type_nom, t.icone 
         FROM metrage_lignes l 
         JOIN metrage_interventions m ON l.intervention_id = m.id
         JOIN metrage_types t ON l.metrage_type_id = t.id
         WHERE m.affaire_id = $affaire_id AND l.statut_traitement != 'TRAITE'"; // On permet PARTIEL ou NON_TRAITE
$lines_metrage = $pdo->query($sqlM)->fetchAll();

// 2. Besoins Chantier (A_CALCULER)
$lines_besoins = $pdo->query("
    SELECT b.*, mp.designation_interne 
    FROM besoins_chantier b 
    LEFT JOIN modeles_profils mp ON b.modele_profil_id = mp.id 
    WHERE b.affaire_id = $affaire_id AND b.statut = 'A_CALCULER'
")->fetchAll();

// 3. Fournisseurs
$fournisseurs = $pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom")->fetchAll();

$page_title = "Générer Commande";
require_once 'header.php';
?>

<div class="main-content">
    <div class="container-fluid mt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                 <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="affaires_detail.php?id=<?= $affaire_id ?>"><?= h($affaire['nom_affaire']) ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Nouvelle Commande</li>
                    </ol>
                </nav>
                <h2 class="fw-bold text-primary">Assistant Commandes Fournisseurs</h2>
                <p class="text-muted">Sélectionnez les articles à regrouper dans cette commande.</p>
            </div>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="cmdForm">
            <input type="hidden" name="affaire_id" value="<?= $affaire_id ?>">
            
            <div class="row">
                <!-- COLONNE GAUCHE : Paramètres Commande -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 mb-4 sticky-top" style="top: 100px; z-index: 10;">
                        <div class="card-header bg-white fw-bold border-bottom">
                            <i class="fas fa-cog me-2 text-primary"></i>Configuration Commande
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Fournisseur</label>
                                <select name="fournisseur_id" class="form-select" required>
                                    <option value="">-- Choisir --</option>
                                    <?php foreach($fournisseurs as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= h($f['nom']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Désignation (Lot)</label>
                                <input type="text" name="designation_cmd" class="form-control" placeholder="Ex: Menuiseries, Vitrages..." required>
                                <div class="form-text">Utile pour identifier ce lot (ex: "Commande Vitrages Véranda").</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Mode de Commande</label>
                                <div class="d-flex gap-2">
                                    <div class="form-check card p-2 flex-fill text-center">
                                        <input class="form-check-input float-none mx-auto d-block mb-1" type="radio" name="mode_commande" value="EMAIL" checked>
                                        <label class="form-check-label small"><i class="fas fa-envelope me-1"></i>Email / PDF</label>
                                    </div>
                                    <div class="form-check card p-2 flex-fill text-center">
                                        <input class="form-check-input float-none mx-auto d-block mb-1" type="radio" name="mode_commande" value="PORTAIL_WEB">
                                        <label class="form-check-label small"><i class="fas fa-globe me-1"></i>Portail Web</label>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 mt-3 shadow-sm">
                                <i class="fas fa-check-circle me-2"></i>Générer la commande
                            </button>
                        </div>
                    </div>
                </div>

                <!-- COLONNE DROITE : Sélection Articles -->
                <div class="col-md-8">
                    
                    <!-- SECTION METRAGE -->
                    <?php if(count($lines_metrage) > 0): ?>
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-ruler-combined me-2"></i>Éléments du Métrage</h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll('metrage')">Tout cocher</button>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach($lines_metrage as $l): ?>
                            <?php $json = json_decode($l['donnees_json'], true); ?>
                            <label class="list-group-item d-flex gap-3 align-items-start cursor-pointer hover-bg-light">
                                <input class="form-check-input flex-shrink-0 mt-2 metrage-cb" type="checkbox" name="items[]" value="M_<?= $l['id'] ?>">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <strong class="text-primary"><?= h($l['type_nom']) ?></strong>
                                        <span class="badge bg-secondary"><?= h($l['localisation']) ?></span>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        <?php 
                                            // Aperçu rapide des options
                                            $details = [];
                                            foreach($json as $v) if(strlen($v) < 20) $details[] = $v;
                                            echo implode(' • ', array_slice($details, 0, 5));
                                        ?>
                                    </div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- SECTION BESOINS VRAC -->
                    <?php if(count($lines_besoins) > 0): ?>
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-cubes me-2"></i>Besoins Vrac / Atelier</h6>
                             <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll('besoin')">Tout cocher</button>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach($lines_besoins as $b): ?>
                            <label class="list-group-item d-flex gap-3 align-items-center cursor-pointer hover-bg-light">
                                <input class="form-check-input flex-shrink-0 besoin-cb" type="checkbox" name="items[]" value="B_<?= $b['id'] ?>">
                                <div>
                                    <strong><?= h($b['quantite']) ?>x <?= h($b['designation_interne']) ?></strong>
                                    <div class="small text-muted">Lg: <?= h($b['longueur_mm']) ?>mm</div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if(count($lines_metrage) == 0 && count($lines_besoins) == 0): ?>
                        <div class="alert alert-success text-center py-5">
                            <div class="fs-1">Check!</div>
                            <h5>Tout est commandé !</h5>
                            <p class="text-muted">Aucun élément en attente de commande pour cette affaire.</p>
                            <a href="affaires_detail.php?id=<?= $affaire_id ?>" class="btn btn-outline-primary mt-3">Retour Affaire</a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAll(type) {
    const cbs = document.querySelectorAll('.' + type + '-cb');
    const firstState = cbs[0].checked;
    cbs.forEach(cb => cb.checked = !firstState);
}
</script>

<?php require_once 'footer.php'; ?>

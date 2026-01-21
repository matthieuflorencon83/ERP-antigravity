<?php
// metrage_validator.php
// Interface de Validation Technique / Dossier Métrage
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$id = $_GET['id'] ?? 0;

// 1. Fetch Mission & Affaire Info & Client Info (Expanded)
$stmt = $pdo->prepare("
    SELECT mi.*, a.numero_prodevis, a.montant_ht, a.nom_affaire, a.designation,
           c.nom_principal as client_nom, c.ville, c.adresse_postale as adresse, c.code_postal, c.telephone_fixe, c.telephone_mobile, c.email_principal as email,
           u.nom_complet as tech_nom
    FROM metrage_interventions mi
    JOIN affaires a ON mi.affaire_id = a.id
    JOIN clients c ON a.client_id = c.id
    LEFT JOIN utilisateurs u ON mi.technicien_id = u.id
    WHERE mi.id = ?
");
$stmt->execute([$id]);
$mission = $stmt->fetch();

if (!$mission) die("Mission introuvable.");

// 2. Fetch Measured Lines
$stmtLines = $pdo->prepare("
    SELECT ml.*, mt.nom as type_nom 
    FROM metrage_lignes ml
    LEFT JOIN metrage_types mt ON ml.metrage_type_id = mt.id
    WHERE ml.intervention_id = ?
    ORDER BY ml.id DESC
");
$stmtLines->execute([$id]);
$lines = $stmtLines->fetchAll();

// 3. Fetch Types
$stmtTypes = $pdo->query("SELECT * FROM metrage_types ORDER BY nom");
$types = $stmtTypes->fetchAll();

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ADD LINE
    if ($_POST['action'] === 'add_line') {
        $type_id = $_POST['metrage_type_id'];
        $emplacement = $_POST['emplacement'] ?? '';
        $largeur = $_POST['largeur'];
        $hauteur = $_POST['hauteur'];
        $obs = $_POST['observations'] ?? '';
        
        $sql = "INSERT INTO metrage_lignes (intervention_id, metrage_type_id, emplacement, largeur, hauteur, observations, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $pdo->prepare($sql)->execute([$id, $type_id, $emplacement, $largeur, $hauteur, $obs]);
        
        // Auto-update status to EN_COURS if A_PLANIFIER
        if ($mission['statut'] === 'A_PLANIFIER' || $mission['statut'] === 'PLANIFIE') {
            $pdo->prepare("UPDATE metrage_interventions SET statut = 'EN_COURS' WHERE id = ?")->execute([$id]);
        }

        redirect("metrage_validator.php?id=$id");
    }

    // DELETE LINE
    if ($_POST['action'] === 'delete_line') {
        $line_id = $_POST['line_id'];
        $pdo->prepare("DELETE FROM metrage_lignes WHERE id = ?")->execute([$line_id]);
        redirect("metrage_validator.php?id=$id");
    }

    // UPDATE INFOS
    if ($_POST['action'] === 'update_infos') {
        $ref = $_POST['numero_prodevis'];
        $nom = $_POST['nom_affaire'];
        $date = $_POST['date_prevue']; 
        
        $pdo->prepare("UPDATE affaires SET numero_prodevis = ?, nom_affaire = ? WHERE id = ?")
            ->execute([$ref, $nom, $mission['affaire_id']]);
        
        // Status logic regarding date
        $newStatut = $mission['statut'];
        if ($date && ($mission['statut'] === 'A_PLANIFIER')) {
             $newStatut = 'PLANIFIE';
        }

        $pdo->prepare("UPDATE metrage_interventions SET date_prevue = ?, statut = ? WHERE id = ?")
            ->execute([$date, $newStatut, $id]);

        redirect("metrage_validator.php?id=$id");
    }

    // VALIDATE
    if ($_POST['action'] === 'validate_technical') {
        $pdo->prepare("UPDATE metrage_interventions SET statut = 'VALIDE', date_realisee = NOW() WHERE id = ?")->execute([$id]);
        redirect('metrage_cockpit.php');
    }
}

$page_title = 'Dossier Métrage #' . $id;
require_once 'header.php';

// Logic for Status Badge
$statusColor = 'secondary';
$statusLabel = 'Inconnu';
$st = $mission['statut'];

if ($st === 'A_PLANIFIER') {
    $statusColor = 'secondary';
    $statusLabel = 'À PLANIFIER';
} elseif ($st === 'PLANIFIE') {
    $statusColor = 'warning';
    $statusLabel = 'PLANIFIÉ';
} elseif ($st === 'EN_COURS' || $st === 'A_REVOIR') {
    $statusColor = 'info';
    $statusLabel = 'EN COURS';
} elseif ($st === 'VALIDE' || $st === 'TERMINE') {
    $statusColor = 'success';
    $statusLabel = 'VALIDÉ';
}
?>

<style>
    /* Custom Styles for Validator Page - DARK MODE */
    .split-layout {
        display: flex;
        height: calc(100vh - 70px); /* Adjust for header */
        overflow: hidden;
        background: #141419; /* Global Dark Background */
        color: #e0e0e0;
    }
    
    /* LEFT PANEL */
    .left-panel {
        width: 35%;
        min-width: 350px;
        background: #1e1e24; /* Slightly lighter sidebar */
        border-right: 1px solid rgba(255, 255, 255, 0.05);
        padding: 25px;
        overflow-y: auto;
    }
    
    /* RIGHT PANEL */
    .right-panel {
        width: 65%;
        background: #141419; /* Main Content Dark */
        padding: 25px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }
    
    /* CARDS & CONTAINERS */
    .client-card, .metrage-line, .card {
        background: rgba(255, 255, 255, 0.03) !important;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        margin-bottom: 20px;
        color: #fff;
    }
    
    /* OVERRIDES FOR BOOTSTRAP IN DARK MODE */
    .split-layout .text-dark { color: #f8f9fa !important; }
    .split-layout .text-muted { color: #adb5bd !important; }
    .split-layout .bg-light { background: rgba(255,255,255,0.05) !important; color: #fff; }
    .split-layout .bg-white { background: transparent !important; }
    .split-layout .border-bottom { border-color: rgba(255,255,255,0.1) !important; }
    .split-layout .border-top { border-color: rgba(255,255,255,0.1) !important; }
    
    /* FORMS */
    .split-layout .form-control, 
    .split-layout .form-select {
        background-color: #2c2c2e;
        border: 1px solid #3a3a3c;
        color: #fff;
        font-weight: 500;
    }
    .split-layout .form-control:focus, 
    .split-layout .form-select:focus {
        background-color: #3a3a3e;
        border-color: #0d6efd;
        color: #fff;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    /* Placeholder color */
    .split-layout ::placeholder { color: #6c757d; opacity: 1; }

    /* SPECIFIC ELEMENTS */
    .info-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba(255,255,255,0.5);
        font-weight: 600;
        margin-bottom: 4px;
    }
    .info-value {
        font-weight: 600;
        color: #fff;
    }
    .status-badge-lg {
        padding: 10px 20px;
        border-radius: 50px;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
        border: 1px solid rgba(255,255,255,0.1);
    }
    
    /* Metrage Line Specifics */
    .metrage-line {
        padding: 15px;
        margin-bottom: 12px;
        transition: transform 0.2s, background 0.2s;
        border-left: 4px solid #0d6efd !important; /* Keep accent */
    }
    .metrage-line:hover {
        transform: translateY(-2px);
        background: rgba(255, 255, 255, 0.06) !important;
        box-shadow: 0 6px 25px rgba(0,0,0,0.3);
    }

    /* Scrollbar */
    .custom-scrollbar::-webkit-scrollbar { width: 8px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #141419; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #3a3a3c; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #4a4a4c; }
</style>

<div class="split-layout">
    
    <!-- LEFT PANEL: ADMIN & CLIENT INFO -->
    <div class="left-panel custom-scrollbar">
        <div class="mb-4">
            <a href="metrage_cockpit.php" class="text-decoration-none text-muted fw-bold">
                <i class="fas fa-chevron-left me-2"></i>Retour Cockpit
            </a>
        </div>

        <h4 class="fw-bold mb-4">Dossier Administratif</h4>

        <!-- CLIENT CARD -->
        <div class="client-card">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                    <i class="fas fa-user fa-lg"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-primary"><?= h($mission['client_nom']) ?></h5>
                    <small class="text-muted">Client</small>
                </div>
            </div>
            
            <div class="mb-3">
                <div class="info-label">Adresse Chantier</div>
                <div class="info-value">
                    <i class="fas fa-map-marker-alt text-danger me-2"></i>
                    <?= h($mission['adresse'] ?? '') ?> <br>
                    <span class="ms-4"><?= h($mission['code_postal']) ?> <?= h($mission['ville']) ?></span>
                </div>
            </div>

            <div class="row g-2">
                <div class="col-6">
                    <div class="info-label">Téléphone Fixe</div>
                    <div class="info-value">
                        <?php if($mission['telephone_fixe']): ?>
                            <a href="tel:<?= h($mission['telephone_fixe']) ?>" class="text-dark text-decoration-none"><i class="fas fa-phone me-2"></i><?= h($mission['telephone_fixe']) ?></a>
                        <?php else: ?>
                            <span class="text-muted fst-italic">Non renseigné</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-6">
                    <div class="info-label">Mobile</div>
                    <div class="info-value">
                        <?php if($mission['telephone_mobile']): ?>
                            <a href="tel:<?= h($mission['telephone_mobile']) ?>" class="text-dark text-decoration-none"><i class="fas fa-mobile-alt me-2"></i><?= h($mission['telephone_mobile']) ?></a>
                        <?php else: ?>
                            <span class="text-muted fst-italic">Non renseigné</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if($mission['email']): ?>
            <div class="mt-3">
                <div class="info-label">Email</div>
                <div class="info-value"><a href="mailto:<?= h($mission['email']) ?>"><?= h($mission['email']) ?></a></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- AFFAIRE & PLANNING FORM -->
        <form method="POST">
            <input type="hidden" name="action" value="update_infos">
            
            <div class="client-card">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h6 class="fw-bold mb-0 text-uppercase"><i class="fas fa-briefcase me-2 text-muted"></i>Affaire</h6>
                    <button type="submit" class="btn btn-sm btn-link text-decoration-none fw-bold">SAUVEGARDER</button>
                </div>

                <div class="mb-3">
                    <label class="info-label">Nom Affaire</label>
                    <input type="text" name="nom_affaire" class="form-control fw-bold" value="<?= h($mission['nom_affaire']) ?>">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="info-label">Réf. Devis</label>
                        <input type="text" name="numero_prodevis" class="form-control font-monospace form-control-sm" value="<?= h($mission['numero_prodevis']) ?>">
                    </div>
                    <div class="col-6">
                        <label class="info-label">Montant HT</label>
                        <input type="text" class="form-control form-control-sm bg-light text-end" value="<?= number_format($mission['montant_ht'], 2, ',', ' ') ?> €" readonly>
                    </div>
                </div>

                <div class="mb-3">
                     <label class="info-label text-primary">Date Intervention / Planning</label>
                     <input type="datetime-local" name="date_prevue" class="form-control border-primary" value="<?= date('Y-m-d\TH:i', strtotime($mission['date_prevue'])) ?>">
                     <small class="text-muted d-block mt-1"><i class="far fa-clock me-1"></i>Definir une date passe le statut à "PLANIFIÉ".</small>
                </div>
            </div>
            
            <div class="client-card">
                 <div class="info-label mb-2">Notes / Désignation</div>
                 <div class="bg-light p-2 rounded small text-secondary">
                     <?= nl2br(h($mission['designation'] ?? 'Pas de notes.')) ?>
                 </div>
            </div>
        </form>
    </div>

    <!-- RIGHT PANEL: METRAGE DETAILS -->
    <div class="right-panel custom-scrollbar">
        
        <!-- HEADER STATUS -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div>
                <h2 class="fw-bold mb-1">Récapitulatif Métrage</h2>
                <div class="text-muted small">
                    <i class="fas fa-hard-hat me-1"></i> Tech: <strong><?= h($mission['tech_nom'] ?? 'Non assigné') ?></strong>
                </div>
            </div>
            <div class="text-end">
                <span class="badge bg-<?= $statusColor ?> status-badge-lg shadow-sm">
                    <?= $statusLabel ?>
                </span>
                <?php if($st !== 'VALIDE' && $st !== 'TERMINE' && !empty($lines)): ?>
                    <form method="POST" class="d-inline-block ms-3">
                        <input type="hidden" name="action" value="validate_technical">
                        <button type="submit" class="btn btn-success btn-lg shadow fw-bold" onclick="return confirm('Valider ce métrage ? Cela archivera le dossier.')">
                            <i class="fas fa-check-circle me-2"></i>VALIDER
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- LISTE MEASURES (RECAP STYLE) -->
        <div class="flex-grow-1">
            <?php if(empty($lines)): ?>
                <div class="text-center py-5 text-muted opacity-50">
                    <i class="fas fa-clipboard-list fa-4x mb-3"></i>
                    <h4>Aucun métrage réalisé</h4>
                    <p>Le technicien n'a pas encore saisi de données pour cette intervention.</p>
                </div>
            <?php else: ?>
                <?php foreach($lines as $line): 
                    // Decode JSON Data
                    $props = json_decode($line['donnees_json'] ?? '{}', true);
                    
                    // Map Dimensions (Unified)
                    $wKeys = ['largeur', 'dimensions_largeur', 'largeur_tablier', 'largeur_passage'];
                    $hKeys = ['hauteur', 'dimensions_hauteur', 'hauteur_tablier', 'hauteur_passage'];
                    
                    $largeur = 0;
                    $hauteur = 0;
                    
                    foreach($wKeys as $wk) { if(isset($props[$wk])) { $largeur = $props[$wk]; break; } }
                    foreach($hKeys as $hk) { if(isset($props[$hk])) { $hauteur = $props[$hk]; break; } }
                    
                    // Fallback to columns if JSON empty
                    if(!$largeur) $largeur = $line['largeur'] ?? 0;
                    if(!$hauteur) $hauteur = $line['hauteur'] ?? 0;

                    // Filter technical keys to keep only "Business" Attributes
                    $excludeKeys = array_merge($wKeys, $hKeys, ['step', 'step_id', 'product_id', 'photo_tableau', 'photo_volet', 'photo_porte']);
                    $specs = [];
                    if(is_array($props)) {
                        foreach($props as $key => $val) {
                            if(!in_array($key, $excludeKeys) && !is_array($val) && !empty($val)) {
                                $specs[$key] = $val;
                            }
                        }
                    }
                ?>
                    <div class="metrage-line">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-primary mb-1"><?= h($line['type_nom']) ?></span>
                                <h5 class="fw-bold text-dark mb-0"><?= h($line['localisation'] ?? $line['emplacement'] ?? 'Emplacement inconnu') ?></h5>
                            </div>
                            <div class="text-end">
                                <div class="fs-4 fw-bold font-monospace"><?= $largeur ?> <span class="text-muted small">x</span> <?= $hauteur ?></div>
                                <span class="badge bg-light text-dark border">mm</span>
                            </div>
                        </div>

                        <!-- Specs Grid -->
                        <?php if(!empty($specs)): ?>
                        <div class="row g-2 mt-2 pt-2 border-top">
                            <?php foreach($specs as $k => $v): ?>
                                <div class="col-auto">
                                    <div class="bg-light rounded px-2 py-1 small border">
                                        <span class="text-muted text-uppercase" style="font-size: 0.7rem;"><?= h(ucfirst(str_replace('_', ' ', $k))) ?>:</span>
                                        <strong class="text-dark ms-1"><?= h($v) ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($line['notes_observateur'])): ?>
                            <div class="mt-2 text-muted small fst-italic"><i class="fas fa-sticky-note me-1"></i><?= h($line['notes_observateur']) ?></div>
                        <?php endif; ?>
                        
                        <?php if($st !== 'VALIDE' && $st !== 'TERMINE'): ?>
                        <div class="text-end mt-2">
                            <form method="POST" onsubmit="return confirm('Supprimer cette ligne ?');">
                                <input type="hidden" name="action" value="delete_line">
                                <input type="hidden" name="line_id" value="<?= $line['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                    <i class="fas fa-trash me-1"></i>Supprimer
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<?php
/**
 * commandes_detail.php
 * Module Achats : Fiche détaillée + Upload + IA
 */

require_once 'auth.php';
// session handled by auth.php
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: text/html; charset=utf-8');

$cmd_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($cmd_id === 0) die("ID Commande invalide.");

$message = "";

// --- 2. RECUPERATION DONNEES (Avant Upload pour avoir le contexte Affaire/Client) ---
$type_cmd = isset($_GET['type']) && $_GET['type'] === 'EXPRESS' ? 'EXPRESS' : 'STANDARD';

try {
    if ($type_cmd === 'STANDARD') {
        // --- LOGIQUE STANDARD (EXISTANTE) ---
        $stmt = $pdo->prepare("
            SELECT c.*, f.nom as fournisseur_nom, f.email_commande, 
                   a.id as affaire_id, a.nom_affaire, a.numero_prodevis,
                   cl.id as client_id, cl.nom_principal as nom_client,
                   c.chemin_pdf_bdc as chemin_pdf_bdc
            FROM commandes_achats c
            JOIN fournisseurs f ON c.fournisseur_id = f.id
            LEFT JOIN affaires a ON c.affaire_id = a.id
            LEFT JOIN clients cl ON a.client_id = cl.id
            WHERE c.id = ?
        ");
        $stmt->execute([$cmd_id]);
        $commande = $stmt->fetch();

        if (!$commande) die("Commande introuvable.");

        $stmt = $pdo->prepare("
            SELECT l.*, ac.designation, ac.ref_fournisseur, fin.nom_couleur
            FROM lignes_achat l
            LEFT JOIN articles ac ON l.article_id = ac.id
            LEFT JOIN finitions fin ON l.finition_id = fin.id
            WHERE l.commande_id = ?
        ");
        $stmt->execute([$cmd_id]);
        $lignes = $stmt->fetchAll();

    } else {
        // --- LOGIQUE EXPRESS (COMMANDE RAPIDE) ---
        // On mappe les champs de 'commandes_express' vers la structure attendue par la vue '$commande'
        $stmt = $pdo->prepare("
            SELECT ce.id, ce.created_at as date_commande, ce.statut, ce.module_type,
                   ce.fournisseur_nom, 
                   'N/A' as email_commande, -- Pas stocké en express
                   a.id as affaire_id, a.nom_affaire, a.numero_prodevis,
                   cl.id as client_id, cl.nom_principal as nom_client,
                   NULL as chemin_pdf_bdc_standard -- Sera généré dynamiquement
            FROM commandes_express ce
            LEFT JOIN affaires a ON ce.affaire_id = a.id
            LEFT JOIN clients cl ON a.client_id = cl.id
            WHERE ce.id = ?
        ");
        $stmt->execute([$cmd_id]);
        $commande = $stmt->fetch();

        if (!$commande) die("Commande Express introuvable.");

        // Reconstruction des champs manquants pour la vue
        $commande['ref_interne'] = "EXP-" . $commande['id'];
        $commande['designation'] = "Commande Rapide (" . $commande['module_type'] . ")";
        
        // Chemin PDF Express (déjà existant)
        // Format du fichier : uploads/commandes_express/CMD_EXP_{ID}_{MODULE}.pdf
        $pdf_path = "uploads/commandes_express/CMD_EXP_" . $commande['id'] . "_" . $commande['module_type'] . ".pdf";
        $commande['chemin_pdf_bdc'] = file_exists($pdf_path) ? $pdf_path : null;
        $commande['chemin_pdf_arc'] = null; // Pas d'ARC en express pour l'instant
        $commande['ticket_id'] = null;
        $commande['raw_text_analyse'] = null;

        // Pas de lignes détaillées en express (c'est le PDF qui fait foi)
        $lignes = [];
    }

    // 0.3 TÂCHES LIÉES (Commun)
    // Attention: tasks table needs 'commande_id' AND 'commande_type' ideally to avoid collision.
    // Assuming for now generic tasks are handled by ID, might need migration if IDs collide.
    // TODO: Add 'commande_type' to tasks table later. For now, simple fetch.
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE commande_id = ? ORDER BY status ASC, importance DESC, created_at DESC");
    $stmt->execute([$cmd_id]);
    $tasks_cmd = $stmt->fetchAll();
    
    // CALCUL DU STATUT DYNAMIQUE basé sur les dates
    $commande['statut_dynamique'] = calculate_order_status($commande);

} catch (Exception $e) { die("SQL Error: " . $e->getMessage()); }

// --- 1. TRAITEMENT UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['pdf_bdc'])) {
    $is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == '1';
    $response = ['success' => false, 'message' => ''];

    try {
        $file = $_FILES['pdf_bdc'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // --- CAS EMAIL (.MSG / .EML) ---
        if ($ext === 'msg' || $ext === 'eml') {
            $archive_email = (isset($_POST['archive_email']) && $_POST['archive_email'] === '1');

            // 1. Récup Config "chemin_clients"
            $stmt = $pdo->prepare("SELECT valeur_config FROM parametres_generaux WHERE cle_config = 'chemin_clients'");
            $stmt->execute();
            $row = $stmt->fetch();
            $base_dir = $row ? $row['valeur_config'] : 'uploads/CLIENTS/';

            // Nettoyage des noms pour le chemin
            $client_name = $commande['nom_client'] ? safe_filename($commande['nom_client']) : 'DIVERS';
            $affaire_name = $commande['nom_affaire'] ? safe_filename($commande['nom_affaire']) : 'SANS_AFFAIRE';
            
            // Dossier EMAILS
            $target_dir = rtrim($base_dir, '/') . '/' . $client_name . '/' . $affaire_name . '/EMAILS/';
            if (!is_dir($target_dir)) @mkdir($target_dir, 0777, true);
            
            $new_filename = date('Y-m-d_H-i-s') . '_' . safe_filename($file['name']);
            $dest_path = $target_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $dest_path)) {
                
                // 2. AUTO-EXTRACTION VIA PYTHON
                $extract_dir = $target_dir . 'attachments_' . time() . '/';
                if (!is_dir($extract_dir)) @mkdir($extract_dir, 0777, true);
                
                $cmd_python = "python tools/extract_email.py " . escapeshellarg($dest_path) . " " . escapeshellarg($extract_dir);
                $output = shell_exec($cmd_python . " 2>&1");
                $json_res = json_decode($output, true);
                
                $pdf_extracted = false;

                // 3. LOGIQUE ARCHIVAGE OU NETTOYAGE
                if ($archive_email) {
                    // CAS OUI : On garde tout (Email + DB + Corps)
                    $stmt = $pdo->prepare("INSERT INTO emails_archives (commande_id, affaire_id, client_id, nom_fichier, chemin_fichier, type_fichier) VALUES (?, ?, ?, ?, ?, ?)");
                    $type = ($ext === 'msg') ? 'MSG' : 'EML';
                    $stmt->execute([$cmd_id, $commande['affaire_id'], $commande['client_id'], $file['name'], $dest_path, $type]);
                    
                    if ($json_res && $json_res['success'] && !empty($json_res['body'])) {
                        $txt_filename = pathinfo($file['name'], PATHINFO_FILENAME) . "_body.txt";
                        file_put_contents($target_dir . $txt_filename, $json_res['body']);
                    }
                }

                // 4. TRAITEMENT PDF (Commun aux deux cas)
                if ($json_res && $json_res['success'] && !empty($json_res['attachments'])) {
                    foreach ($json_res['attachments'] as $att_path) {
                        $att_ext = strtolower(pathinfo($att_path, PATHINFO_EXTENSION));
                        if ($att_ext === 'pdf') {
                            $stmt = $pdo->prepare("SELECT valeur_config FROM parametres_generaux WHERE cle_config = 'chemin_achats'");
                            $stmt->execute();
                            $row = $stmt->fetch();
                            $bdc_dir = $row ? $row['valeur_config'] : 'uploads/BDC/';
                            if (!is_dir($bdc_dir)) @mkdir($bdc_dir, 0777, true);
                            
                            $nom_bdc = "BDC_" . $cmd_id . "_EXTRACT_" . time() . ".pdf";
                            $chemin_bdc = rtrim($bdc_dir, '/') . '/' . $nom_bdc;
                            
                            if (rename($att_path, $chemin_bdc)) {
                                $stmt = $pdo->prepare("UPDATE commandes_achats SET chemin_pdf_bdc = ? WHERE id = ?");
                                $stmt->execute([$chemin_bdc, $cmd_id]);
                                $pdf_extracted = true;
                                $commande['chemin_pdf_bdc'] = $chemin_bdc; // Update local variable
                                break; 
                            }
                        }
                    }
                }

                // CAS NON : On supprime l'email source (.msg/.eml) car on ne veut pas l'archiver
                if (!$archive_email) {
                    if (file_exists($dest_path)) unlink($dest_path);
                }
                
                // Nettoyage dossier extraction (toujours)
                array_map('unlink', glob("$extract_dir/*.*"));
                rmdir($extract_dir);

                if ($pdf_extracted) {
                    $response['success'] = true;
                    $response['type'] = 'EXTRACTED_PDF';
                    $archive_msg = $archive_email ? "Email archivé." : "Email non conservé.";
                    $response['message'] = "$archive_msg PDF extrait ! Analyse IA...";
                } else {
                    $response['success'] = true; // Pas d'erreur technique, mais pas de PDF
                    $response['type'] = 'EMAIL_ONLY';
                    $response['message'] = "Aucun PDF trouvé dans l'email.";
                }

            } else { throw new Exception("Erreur sauvegarde email temporaire."); }
            
        } 
        // --- CAS PDF ---
        elseif ($ext === 'pdf') {
            $stmt = $pdo->prepare("SELECT valeur_config FROM parametres_generaux WHERE cle_config = 'chemin_achats'");
            $stmt->execute();
            $row = $stmt->fetch();
            $dossier_racine = $row ? $row['valeur_config'] : 'uploads/BDC/';
            if (!is_dir($dossier_racine)) @mkdir($dossier_racine, 0777, true);

            $nom_fichier = "BDC_" . $cmd_id . "_" . time() . ".pdf";
            $chemin_dest = rtrim($dossier_racine, '/') . '/' . $nom_fichier;

            if (move_uploaded_file($file['tmp_name'], $chemin_dest)) {
                $stmt = $pdo->prepare("UPDATE commandes_achats SET chemin_pdf_bdc = ? WHERE id = ?");
                $stmt->execute([$chemin_dest, $cmd_id]);
                $commande['chemin_pdf_bdc'] = $chemin_dest; // Update local variable
                
                $response['success'] = true;
                $response['type'] = 'PDF';
                $response['path'] = $chemin_dest; // path for JS viewer
                $response['message'] = "PDF Uploadé.";
                // Pas de refresh ici si AJAX
            } else { throw new Exception("Erreur sauvegarde PDF."); }
        } else { throw new Exception("Format non supporté."); }
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        // Fallback classique (si formulaire posté sans JS)
        $message = $response['success'] ? "<div class='alert alert-success'>".$response['message']."</div>" : "<div class='alert alert-danger'>".$response['message']."</div>";
        if($response['success'] && $ext === 'pdf') header("refresh:1");
    }
}

$total_ht = 0;
foreach($lignes as $l) $total_ht += $l['qte_commandee'] * $l['prix_unitaire_achat'];

$page_title = "Commande " . $commande['ref_interne'];
require_once 'header.php';
?>

<div class="main-content">
    <div class="container-fluid mt-4">
    
    <?= $message ?>

    <!-- COMPACT HEADER -->
    <div class="card shadow-sm mb-3 border-0">
        <div class="card-body p-3">
            <div class="row align-items-center g-2">
                <div class="col-auto">
                    <small class="text-muted d-block">Date Création</small>
                    <strong><?= date('d/m/Y', strtotime($commande['date_commande'] ?? $commande['created_at'] ?? 'now')) ?></strong>
                </div>
                <div class="col-auto border-start ps-3">
                    <small class="text-muted d-block">Affaire</small>
                    <strong><?= h($commande['nom_affaire'] ?? '-') ?></strong>
                </div>
                <div class="col-auto border-start ps-3">
                    <small class="text-muted d-block">N° Commande</small>
                    <strong class="text-primary"><?= h($commande['ref_interne']) ?></strong>
                </div>
                <div class="col-auto border-start ps-3">
                    <small class="text-muted d-block">Fournisseur</small>
                    <strong><?= h($commande['fournisseur_nom']) ?></strong>
                </div>
                <div class="col border-start ps-3">
                    <small class="text-muted d-block">Désignation</small>
                    <strong><?= h($commande['designation'] ?? '-') ?></strong>
                </div>
                <div class="col-auto ms-auto">
                    <!-- DROPZONE HEADER -->
                    <div id="header-dropzone" class="d-flex align-items-center justify-content-center border border-2 border-primary border-dashed rounded px-3 py-2 text-primary user-select-none" style="cursor: pointer; transition: 0.2s;">
                        <i class="fas fa-magic me-2"></i>
                        <span class="small fw-bold">Glisser PDF = Analyse IA</span>
                        <input type="file" id="header-file-input" class="d-none" accept=".pdf,.msg,.eml">
                    </div>
                </div>
                <div class="col-auto">
                    <a href="commandes_liste.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                        <i class="fas fa-arrow-left me-1"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- STATUS TIMELINE -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <!-- Statut Badge -->
                <div class="col-auto">
                    <div class="text-center">
                        <small class="text-muted d-block mb-1">Statut</small>
                        <?= badge_statut($commande['statut_dynamique']) ?>
                    </div>
                </div>
                
                <div class="col">
                    <div class="d-flex justify-content-between align-items-center position-relative">
                        <!-- Timeline Line -->
                        <div class="position-absolute w-100" style="height: 2px; background: #dee2e6; top: 20px; left: 0; z-index: 0;"></div>
                        
                        <!-- Timeline Steps -->
                        <?php
                        $timeline = [
                            ['label' => 'En Attente', 'date' => $commande['date_en_attente'] ?? null, 'icon' => 'clock'],
                            ['label' => 'Commandé', 'date' => $commande['date_commande'] ?? null, 'icon' => 'shopping-cart'],
                            ['label' => 'ARC Reçu', 'date' => $commande['date_arc_recu'] ?? null, 'icon' => 'file-signature'],
                            ['label' => 'Liv. Prévue', 'date' => $commande['date_livraison_prevue'] ?? null, 'icon' => 'calendar-check'],
                            ['label' => 'Liv. Réelle', 'date' => $commande['date_livraison_reelle'] ?? null, 'icon' => 'truck']
                        ];
                        
                        foreach ($timeline as $step):
                            $has_date = !empty($step['date']);
                            $circle_class = $has_date ? 'bg-success border-success' : 'bg-white border-secondary';
                            $text_class = $has_date ? 'text-dark fw-bold' : 'text-muted';
                        ?>
                        <div class="text-center position-relative" style="z-index: 1; flex: 1;">
                            <div class="rounded-circle border border-2 <?= $circle_class ?> d-inline-flex align-items-center justify-content-center mb-2" style="width: 40px; height: 40px; background: white;">
                                <i class="fas fa-<?= $step['icon'] ?> <?= $has_date ? 'text-white' : 'text-secondary' ?>"></i>
                            </div>
                            <div>
                                <small class="d-block text-muted" style="font-size: 0.75rem;"><?= $step['label'] ?></small>
                                <small class="d-block <?= $text_class ?>" style="font-size: 0.8rem;">
                                    <?= $has_date ? date('d/m/Y', strtotime($step['date'])) : '-' ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Montant HT -->
                <div class="col-auto border-start ps-4">
                    <div class="text-center">
                        <small class="text-muted d-block mb-1">Montant HT</small>
                        <h4 class="mb-0 text-primary fw-bold"><?= number_format($total_ht, 2, ',', ' ') ?> €</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- COLONNE GAUCHE -->
        <div class="col-md-5">
            
            <!-- DOCUMENT & IA -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-secondary text-white fw-bold py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-file-pdf me-2"></i>Visualiseur</h6>
                </div>
                <div class="card-body p-0">
                    <!-- VIEWER PDF -->
                    <iframe id="main-pdf-viewer" src="about:blank" class="pdf-viewer" style="width: 100%; height: 600px; border: none;"></iframe>
                    
                    <!-- INITIALIZATION JS (To load first available doc) -->
                    <?php 
                        $default_doc = '';
                        // Ajout de parametres pour cacher la miniature ("navpanes=0")
                        if($commande['chemin_pdf_bdc']) $default_doc = 'view.php?path='.urlencode($commande['chemin_pdf_bdc']) . '#navpanes=0&toolbar=1&scrollbar=1&view=FitH';
                        elseif(!empty($commande['chemin_pdf_arc'])) $default_doc = 'view.php?path='.urlencode($commande['chemin_pdf_arc']) . '#navpanes=0&toolbar=1&scrollbar=1&view=FitH';
                    ?>
                   <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const viewer = document.getElementById('main-pdf-viewer');
                            const defaultSrc = "<?= $default_doc ?>";
                            if(defaultSrc) viewer.src = defaultSrc;
                            else {
                                viewer.srcdoc = '<div style="text-align:center; padding-top:50px; color:#aaa; font-family:sans-serif;"><p>Aucun document à afficher.</p></div>';
                            }
                        });
                        
                        function loadPdfInViewer(path) {
                            // On ajoute aussi les params ici
                            document.getElementById('main-pdf-viewer').src = 'view.php?path=' + encodeURIComponent(path) + '#navpanes=0&toolbar=1&scrollbar=1&view=FitH';
                        }
                   </script>
                </div>
                
                <!-- LISTE DES FICHIERS (BOX DESSOUS) -->
                <div class="card-footer bg-light border-top">
                    <h6 class="fw-bold small text-uppercase text-muted mb-3">Fichiers disponibles</h6>
                    <div class="list-group">
                        
                        <!-- 1. BON DE COMMANDE -->
                        <div class="list-group-item d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-invoice text-danger me-3 fs-4"></i>
                                <div>
                                    <div class="fw-bold">Bon de Commande</div>
                                    <?php if($commande['chemin_pdf_bdc']): ?>
                                        <small class="text-success"><i class="fas fa-check me-1"></i>Disponible</small>
                                    <div class="mb-3">
                                <label class="text-muted small text-uppercase">Date de Commande</label>
                                <div class="fw-bold fs-5"><?= date('d/m/Y', strtotime($commande['date_commande'])) ?></div>
                            </div>
                            
                            <?php if(!empty($commande['ticket_id'])): 
                                // Récup infos ticket light
                                $tinfo = $pdo->query("SELECT numero_ticket FROM sav_tickets WHERE id = " . $commande['ticket_id'])->fetch();
                            ?>
                            <div class="mb-3">
                                <label class="text-muted small text-uppercase">Origine</label>
                                <div>
                                    <a href="sav_mobile_diag.php?id=<?= $commande['ticket_id'] ?>" class="btn btn-sm btn-warning text-dark fw-bold">
                                        <i class="fas fa-ticket-alt me-1"></i>Ticket SAV #<?= $tinfo['numero_ticket'] ?? '?' ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="text-muted small text-uppercase">Statut</label>
                                <div class="mt-1"><?= badge_statut($commande['statut']) ?></div>
                            </div>        <button onclick="loadPdfInViewer('<?= addslashes($commande['chemin_pdf_bdc']) ?>')" class="btn btn-sm btn-outline-primary" title="Voir"><i class="fas fa-eye"></i></button>
                                    <a href="delete_file.php?type=bdc&id=<?= $cmd_id ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Confirmer suppression ?')"><i class="fas fa-trash"></i></a>
                                <?php else: ?>
                                    <small class="text-muted">Non disponible</small>
                                <?php endif; ?>
                                </div>
                            </div>
                            <div class="btn-group">
                                <?php if($commande['chemin_pdf_bdc']): ?>
                                    <button onclick="loadPdfInViewer('<?= addslashes($commande['chemin_pdf_bdc']) ?>')" class="btn btn-sm btn-outline-primary" title="Voir"><i class="fas fa-eye"></i></button>
                                    <a href="delete_file.php?type=bdc&id=<?= $cmd_id ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Confirmer suppression ?')"><i class="fas fa-trash"></i></a>
                                <?php else: ?>
                                    <button onclick="document.getElementById('header-file-input').click()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-upload me-1"></i>Ajouter</button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 2. ARC -->
                        <div class="list-group-item d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-contract text-warning me-3 fs-4"></i>
                                <div>
                                    <div class="fw-bold">Accusé de Réception (ARC)</div>
                                    <?php if(!empty($commande['chemin_pdf_arc'])): ?>
                                        <small class="text-success"><i class="fas fa-check me-1"></i>Reçu le <?= !empty($commande['date_arc_recu']) ? date_fr($commande['date_arc_recu']) : '?' ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Non reçu</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="btn-group">
                                <?php if(!empty($commande['chemin_pdf_arc'])): ?>
                                    <button onclick="loadPdfInViewer('<?= addslashes($commande['chemin_pdf_arc']) ?>')" class="btn btn-sm btn-outline-primary" title="Voir"><i class="fas fa-eye"></i></button>
                                    <a href="delete_file.php?type=arc&id=<?= $cmd_id ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Confirmer suppression ?')"><i class="fas fa-trash"></i></a>
                                <?php else: ?>
                                    <button onclick="alert('Pour ajouter un ARC, glissez le fichier dans la zone d\'analyse ci-dessus.')" class="btn btn-sm btn-outline-secondary"><i class="fas fa-upload me-1"></i>Ajouter</button>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- RESULTAT IA -->
             <?php if (!empty($commande['raw_text_analyse'])): ?>
            <div class="card border-warning mb-4 shadow-sm">
                <div class="card-header bg-warning text-dark fw-bold">
                    <i class="fas fa-robot me-2"></i>Dernière Analyse IA
                </div>
                <div class="card-body bg-light small">
                    <div class="mb-2"><strong>Statut:</strong> <?= $commande['statut_ia'] ?></div>
                    <pre class="mb-0 text-muted" style="max-height: 150px; overflow-y: auto;"><?= htmlspecialchars(substr($commande['raw_text_analyse'], 0, 800)) ?></pre>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- COLONNE DROITE : CONTENU DU PANIER -->
        <!-- ZONE RESULTATS IA (Invisible par défaut) -->
        <div id="resultat-ia" class="card shadow-sm mb-4 border-success d-none">
            <div class="card-header bg-success text-white fw-bold">
                <i class="fas fa-robot"></i> Résultat Analyse IA
            </div>
            <div class="card-body">
                <div id="ia-console"></div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-bdc-btn" data-bs-toggle="tab" data-bs-target="#tab-bdc" type="button" role="tab">
                                <i class="fas fa-file-invoice me-1"></i> BDC
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-arc-btn" data-bs-toggle="tab" data-bs-target="#tab-arc" type="button" role="tab">
                                <i class="fas fa-file-signature me-1"></i> ARC
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-bl-btn" data-bs-toggle="tab" data-bs-target="#tab-bl" type="button" role="tab">
                                <i class="fas fa-truck me-1"></i> BL
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-comparatif-btn" data-bs-toggle="tab" data-bs-target="#tab-comparatif" type="button" role="tab">
                                <i class="fas fa-balance-scale me-1"></i> Comparatif
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body p-0">
                    <div class="tab-content">
                        <!-- TAB BDC -->
                        <div class="tab-pane fade show active p-4" id="tab-bdc" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-file-invoice me-2"></i>Bon de Commande</h6>
                                <button class="btn btn-sm btn-primary" onclick="document.getElementById('header-file-input').click()">
                                    <i class="fas fa-upload me-1"></i> Déposer BDC
                                </button>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Réf</th>
                                            <th>Désignation</th>
                                            <th class="text-center">Qté</th>
                                            <th class="text-end">P.U. HT</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bdc-lines-body">
                                        <?php if(empty($lignes)): ?>
                                            <tr><td colspan="5" class="text-center text-muted py-4">Aucune ligne extraite. Déposez un BDC pour analyse.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($lignes as $l): ?>
                                            <tr>
                                                <td class="small fw-bold text-primary"><?= h($l['ref_fournisseur']) ?></td>
                                                <td><?= h($l['designation'] ?? '-') ?></td>
                                                <td class="text-center fw-bold"><?= $l['qte_commandee'] + 0 ?></td>
                                                <td class="text-end"><?= prix_fr($l['prix_unitaire_achat']) ?></td>
                                                <td class="text-end fw-bold"><?= prix_fr($l['qte_commandee'] * $l['prix_unitaire_achat']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="4" class="text-end fw-bold">TOTAL HT</td>
                                            <td class="text-end fw-bold text-primary"><?= prix_fr($total_ht) ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        
                        <!-- TAB ARC -->
                        <div class="tab-pane fade p-4" id="tab-arc" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-bold text-warning"><i class="fas fa-file-signature me-2"></i>Accusé de Réception</h6>
                                <button class="btn btn-sm btn-warning" onclick="alert('Déposez un ARC dans la zone principale')">
                                    <i class="fas fa-upload me-1"></i> Déposer ARC
                                </button>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Réf</th>
                                            <th>Désignation</th>
                                            <th class="text-center">Qté Confirmée</th>
                                            <th class="text-end">P.U. HT</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="arc-lines-body">
                                        <tr><td colspan="5" class="text-center text-muted py-4">Aucune ligne ARC. Déposez un ARC pour analyse.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- TAB BL -->
                        <div class="tab-pane fade p-4" id="tab-bl" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-bold text-success"><i class="fas fa-truck me-2"></i>Bon de Livraison</h6>
                                <button class="btn btn-sm btn-success" onclick="alert('Déposez un BL dans la zone principale')">
                                    <i class="fas fa-upload me-1"></i> Déposer BL
                                </button>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Réf</th>
                                            <th>Désignation</th>
                                            <th class="text-center">Qté Livrée</th>
                                            <th class="text-center">Date Livraison</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bl-lines-body">
                                        <tr><td colspan="4" class="text-center text-muted py-4">Aucune ligne BL. Déposez un BL pour analyse.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- TAB COMPARATIF -->
                        <div class="tab-pane fade p-4" id="tab-comparatif" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-bold text-danger"><i class="fas fa-balance-scale me-2"></i>Comparatif BDC vs ARC</h6>
                                <button class="btn btn-sm btn-outline-danger" onclick="compareDocuments()">
                                    <i class="fas fa-sync me-1"></i> Comparer
                                </button>
                            </div>
                            
                            <div id="comparatif-alerts" class="mb-3"></div>
                            
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Réf</th>
                                            <th>Désignation</th>
                                            <th class="text-center">Qté BDC</th>
                                            <th class="text-center">Qté ARC</th>
                                            <th class="text-center">Écart</th>
                                            <th class="text-end">Prix BDC</th>
                                            <th class="text-end">Prix ARC</th>
                                        </tr>
                                    </thead>
                                    <tbody id="comparatif-body">
                                        <tr><td colspan="7" class="text-center text-muted py-4">Déposez un BDC et un ARC pour activer la comparaison.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION TÂCHES -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold text-petrol"><i class="fas fa-check-square me-2"></i>Tâches liées à cette commande</h5>
                    <button class="btn btn-petrol rounded-pill btn-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                        <i class="fas fa-plus me-2"></i>Nouvelle Tâche
                    </button>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($tasks_cmd)): ?>
                        <div class="text-center p-4 text-muted small">
                            Pas de tâches pour le moment.
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($tasks_cmd as $t): ?>
                                <li class="list-group-item p-3 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                         <a href="tasks.php?toggle_id=<?= $t['id'] ?>&redirect=commandes_detail.php?id=<?= $cmd_id ?>" class="text-decoration-none">
                                            <?php if ($t['status'] === 'completed'): ?>
                                                <div class="rounded-circle bg-success border border-success d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                    <i class="fas fa-check text-white small"></i>
                                                </div>
                                            <?php else: ?>
                                                <div class="rounded-circle border border-2 border-secondary d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                    <i class="fas fa-check text-white small" style="opacity: 0;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </a>
                                        <div class="<?= $t['status'] === 'completed' ? 'text-decoration-line-through text-muted' : '' ?>">
                                            <span class="fw-bold"><?= htmlspecialchars($t['title']) ?></span>
                                            <?php if($t['importance'] == 'high'): ?>
                                                <span class="badge bg-danger-subtle text-danger small ms-2">Urgent</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <a href="tasks.php?delete_id=<?= $t['id'] ?>&redirect=commandes_detail.php?id=<?= $cmd_id ?>" class="text-danger small" onclick="return confirm('Supprimer ?');"><i class="fas fa-trash-alt"></i></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

                </div>
            </div>
        </div>
    </div>

    <!-- ZONE DE VALIDATION IA (Cachée par défaut) -->
    <div id="ai-validation-container" class="card shadow-sm mb-4 border-0 d-none" style="border-left: 5px solid #0d6efd !important;">
        <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-robot me-2 text-primary"></i>Validation de l'Analyse IA
                <br><span id="ai-type-doc" class="badge bg-secondary">Analyse en cours...</span>
                <input type="hidden" id="ai-doc-type-input">
            </div>
            <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Vérifiez avant import</span>
        </div>
        <div class="card-body bg-light">
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">N° Document</label>
                    <input type="text" id="ai-num-doc" class="form-control fw-bold text-primary">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Date Document</label>
                    <input type="date" id="ai-date-doc" class="form-control">
                </div>
                <!-- CHAMPS SPECIFIQUES ARC -->
                <div class="col-md-3 d-none" id="ai-arc-fields">
                    <label class="form-label fw-bold small text-uppercase text-success">LIVRAISON PRÉVUE</label>
                    <input type="date" id="ai-date-liv" class="form-control border-success">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Total HT (€)</label>
                    <input type="number" step="0.01" id="ai-total-ht" class="form-control fw-bold">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-12">
                    <button class="btn btn-primary w-100 fw-bold shadow-sm" id="btn-valider-ai" onclick="validerImportIA()">
                        <i class="fas fa-check-circle me-2"></i>VALIDER & IMPORTER
                    </button>
                </div>
            </div>

            <div class="table-responsive bg-white rounded shadow-sm">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 15%">Réf. Frs</th>
                            <th style="width: 40%">Désignation</th>
                            <th style="width: 10%">Quantité</th>
                            <th style="width: 15%">Px Unitaire</th>
                            <th style="width: 5%"></th>
                        </tr>
                    </thead>
                    <tbody id="ai-lines-body">
                        <!-- JS injectera les lignes ici -->
                    </tbody>
                </table>
            </div>

            <div class="text-end mt-3">
                <button class="btn btn-link text-muted text-decoration-none btn-sm" onclick="document.getElementById('ai-validation-container').classList.add('d-none')">
                    Annuler l'import
                </button>
            </div>
        </div>
    </div>


    <!-- MODAL ADD TASK -->
    <div class="modal fade" id="addTaskModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <form method="POST" action="tasks.php">
              <input type="hidden" name="action" value="add_task">
              <input type="hidden" name="commande_id" value="<?= $cmd_id ?>">
              <input type="hidden" name="redirect" value="commandes_detail.php?id=<?= $cmd_id ?>">
              
              <div class="modal-header bg-petrol text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>Tâche pour <?= htmlspecialchars($commande['ref_interne']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold">Titre de la tâche</label>
                    <input type="text" class="form-control form-control-lg" name="title" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Importance</label>
                    <select class="form-select" name="importance">
                        <option value="normal">Normale</option>
                        <option value="high">🔴 Haute Priorité</option>
                    </select>
                </div>
              </div>
              <div class="modal-footer bg-light">
                <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-petrol px-4 fw-bold">Ajouter</button>
              </div>
          </form>
        </div>
      </div>
    </div>

<script>
// Variable globale pour stocker temporairement le résultat de l'IA
let resultatsIA = null;

function lancerAnalyseIA(idCmd) { // force argument removed, auto-run
    const btn = document.getElementById('btn-ia-check');
    const zoneResultat = document.getElementById('ai-validation-container'); // Changed to ai-validation-container
    
    // UI Loading
    if(btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Lecture en cours...';
    }
    if(zoneResultat) {
        zoneResultat.classList.remove('d-none');
        zoneResultat.scrollIntoView({ behavior: 'smooth' });
    }

    // 2. Appel API (Parsing)
    const formData = new FormData();
    formData.append('commande_id', idCmd);

    fetch('ai_parser.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if(btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-magic me-1"></i>Anal. IA'; // Reset button text
        }
        
        if(data.success && data.data) {
            // Affichage de l'interface de validation
            afficherValidationIA(data.data);
        } else {
            alert("Erreur analyse: " + (data.message || 'Inconnue'));
            if(zoneResultat) zoneResultat.classList.add('d-none'); // Hide on error
        }
    })
    .catch(err => {
        console.error(err);
        alert("Erreur technique lors de l'appel IA.");
        if(btn) btn.disabled = false;
        if(zoneResultat) zoneResultat.classList.add('d-none'); // Hide on error
    });
}

function afficherValidationIA(data) {
    const container = document.getElementById('ai-validation-container');
    if(!container) return;

    container.classList.remove('d-none');
    
    // Détection Type
    const isArc = (data.type_document === 'ARC');
    document.getElementById('ai-type-doc').innerText = isArc ? 'ACCUSÉ DE RÉCEPTION (ARC)' : 'COMMANDE / FACTURE';
    document.getElementById('ai-type-doc').className = isArc ? 'badge bg-warning text-dark mb-2' : 'badge bg-info text-dark mb-2';
    
    // Champs ID cachés
    document.getElementById('ai-doc-type-input').value = data.type_document || 'COMMANDE';

    // Pré-remplissage En-tête
    document.getElementById('ai-num-doc').value = data.numero_document || '';
    document.getElementById('ai-date-doc').value = data.date_document || '';
    document.getElementById('ai-total-ht').value = data.montant_total_ht || '';
    
    // Champs ARC
    const arcFields = document.getElementById('ai-arc-fields');
    if(isArc) {
        arcFields.classList.remove('d-none');
        document.getElementById('ai-date-liv').value = data.date_livraison_prevue || '';
    } else {
        arcFields.classList.add('d-none');
    }
    
    // Remplissage Tableau
    const tbody = document.getElementById('ai-lines-body');
    tbody.innerHTML = '';
    
    if(data.lignes_articles && data.lignes_articles.length > 0) {
        data.lignes_articles.forEach((line, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" class="form-control form-control-sm" name="ref[]" value="${line.reference || ''}"></td>
                <td><input type="text" class="form-control form-control-sm" name="des[]" value="${line.designation || ''}"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm" name="qte[]" value="${line.quantite || 1}"></td>
                <td><input type="number" step="0.001" class="form-control form-control-sm" name="pu[]" value="${line.prix_unitaire || 0}"></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
            `;
            tbody.appendChild(row);
        });
    } else {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Aucune ligne détectée.</td></tr>';
    }
    
    // Scroll vers le container
    container.scrollIntoView({ behavior: 'smooth' });
}

function validerImportIA() {
    const cmdId = <?= $cmd_id ?>;
    const typeDoc = document.getElementById('ai-doc-type-input').value;
    const numDoc = document.getElementById('ai-num-doc').value;
    const dateDoc = document.getElementById('ai-date-doc').value;
    const totalHt = document.getElementById('ai-total-ht').value;
    const dateLiv = document.getElementById('ai-date-liv').value;
    
    const lignes = [];
    document.querySelectorAll('#ai-lines-body tr').forEach(row => {
        const inputs = row.querySelectorAll('input');
        if(inputs.length >= 4) {
            lignes.push({
                reference: inputs[0].value,
                designation: inputs[1].value,
                quantite: inputs[2].value,
                prix_unitaire: inputs[3].value
            });
        }
    });

    // Alertes validation
    if(typeDoc === 'ARC') {
        if(!confirm("Vous validez un ARC. Les dates seront mises à jour. Continuer ?")) return;
    } else {
        if(lignes.length === 0 && !confirm("Aucune ligne à importer. Continuer ?")) return;
    }

    const btnValider = document.getElementById('btn-valider-ai');
    const originalText = btnValider.innerHTML;
    btnValider.disabled = true;
    btnValider.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';

    fetch('commandes_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'save_ai_lines',
            commande_id: cmdId,
            type_document: typeDoc,
            numero_document: numDoc,
            date_document: dateDoc,
            date_livraison_prevue: dateLiv,
            montant_total_ht: totalHt,
            lignes: lignes
        })
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            alert("Validation réussie !");
            location.reload();
        } else {
            alert("Erreur: " + data.message);
            btnValider.disabled = false;
            btnValider.innerHTML = originalText;
        }
    })
    .catch(err => {
        console.error(err);
        alert("Erreur technique.");
        btnValider.disabled = false;
        btnValider.innerHTML = originalText;
    });
}

// GESTION DROPZONE HEADER
document.addEventListener('DOMContentLoaded', () => {
    const dropzone = document.getElementById('header-dropzone');
    const input = document.getElementById('header-file-input');
    const cmdId = <?= $cmd_id ?>;

    if(dropzone && input) {
        // Clic pour ouvrir
        dropzone.addEventListener('click', () => input.click());

        // Changement fichier -> Upload
        input.addEventListener('change', () => {
            if(input.files.length) uploadFile(input.files[0]);
        });

        // Drag & Drop visual effects
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.style.background = 'rgba(255,255,255,0.4)';
        });

        dropzone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropzone.style.background = 'rgba(255,255,255,0.15)';
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.style.background = 'rgba(255,255,255,0.15)';
            if(e.dataTransfer.files.length) uploadFile(e.dataTransfer.files[0]);
        });
    }

    function uploadFile(file) {
        // CHECK EXTENSION
        const ext = file.name.split('.').pop().toLowerCase();
        
        // Par défaut on archive (PDF direct)
        let archiveEmail = true; 

        if(ext === 'pdf') {
            // PDF: Direct Upload
        } else if (ext === 'msg' || ext === 'eml') {
            // EMAIL: Choix Archive ou Juste Extraction
            archiveEmail = confirm("Souhaitez-vous ARCHIVER cet email (et son texte) ?\n\n[OK] = OUI (Archive Email + Texte + PDF)\n[Annuler] = NON (Extrait PDF seulement, supprime l'email)");
        } else {
            alert('Seuls les fichiers PDF, MSG et EML sont acceptés.');
            return;
        }

        const originalHtml = dropzone.innerHTML;
        dropzone.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
        dropzone.style.pointerEvents = 'none';

        const formData = new FormData();
        formData.append('pdf_bdc', file);
        
        // On envoie le choix utilisateur
        formData.append('archive_email', archiveEmail ? '1' : '0');

        // Ajout paramètre AJAX pour réponse JSON
        fetch(window.location.href + (window.location.href.includes('?') ? '&' : '?') + 'ajax=1', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // SUCCESS
                dropzone.innerHTML = '<i class="fas fa-check"></i> Uploadé';
                setTimeout(() => dropzone.innerHTML = originalHtml, 2000);
                dropzone.style.pointerEvents = 'auto';

                if(data.type === 'PDF' || data.type === 'EXTRACTED_PDF') {
                    // 1. Update Viewer
                    if(data.path && typeof loadPdfInViewer === 'function') {
                        loadPdfInViewer(data.path);
                    }
                    // 2. AUTO-ANALYSE IA SANS RELOAD
                    lancerAnalyseIA(cmdId); 
                } else {
                    // SI EMAIL SEUL -> RELOAD
                    alert(data.message);
                    location.reload();
                }
            } else {
                alert('Erreur: ' + data.message);
                dropzone.innerHTML = originalHtml;
                dropzone.style.pointerEvents = 'auto';
            }
        })
        .catch(error => {
            console.error(error);
            alert('Erreur Upload (Console)');
            dropzone.innerHTML = originalHtml;
            dropzone.style.pointerEvents = 'auto';
        });
    }
});

// FONCTION COMPARAISON BDC vs ARC (Placeholder)
function compareDocuments() {
    const alertsDiv = document.getElementById('comparatif-alerts');
    const tbody = document.getElementById('comparatif-body');
    
    // TODO: Implement actual comparison logic
    // For now, show placeholder message
    alertsDiv.innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Fonction en développement</strong><br>
            La comparaison automatique BDC vs ARC sera disponible après l'implémentation de l'analyse IA pour les documents ARC.
        </div>
    `;
    
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Comparaison en cours de développement...</td></tr>';
}
</script>
</body>
</html>

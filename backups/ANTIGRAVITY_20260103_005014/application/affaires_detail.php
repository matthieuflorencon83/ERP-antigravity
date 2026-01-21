<?php
/**
 * affaires_detail.php
 * Module : Cockpit Affaire (Vue centrale)
 * @version 2.0 (Menu Unifié)
 */
require_once 'auth.php'; // Securite (Include DB)
require_once 'core/functions.php';

// 1. Sécurité HTTP
secure_headers();

// Force UTF-8
header('Content-Type: text/html; charset=utf-8');

$affaire_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($affaire_id === 0) die("ID Affaire invalide.");

// 0. TRAITEMENT FORMULAIRE (Mise à jour Infos & Statut)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_sales') {
    $montant_ht = floatval($_POST['montant_ht']);
    $date_sign  = !empty($_POST['date_signature']) ? $_POST['date_signature'] : null;
    $statut     = $_POST['statut'];

    // Si on passe en Signé et qu'il y a une date, c'est bon.
    // On met à jour
    $sql = "UPDATE affaires SET montant_ht = ?, date_signature = ?, statut = ?, designation = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$montant_ht, $date_sign, $statut, $_POST['designation'], $affaire_id]);
    
    // Refresh pour affichage
    header("Location: affaires_detail.php?id=$affaire_id&success=1");
    exit;
}

// 0.2 UPDATE PLANNING
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_planning') {
    $statut_c = $_POST['statut_chantier'];
    $d_debut  = !empty($_POST['date_pose_debut']) ? $_POST['date_pose_debut'] : null;
    $d_fin    = !empty($_POST['date_pose_fin']) ? $_POST['date_pose_fin'] : null;
    $equipe   = $_POST['equipe_pose'];

    $sql = "UPDATE affaires SET statut_chantier = ?, date_pose_debut = ?, date_pose_fin = ?, equipe_pose = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$statut_c, $d_debut, $d_fin, $equipe, $affaire_id]);
    
    header("Location: affaires_detail.php?id=$affaire_id&success=planning_saved#planning");
    exit;
}



try {
    // 1. INFO AFFAIRE
    $stmt = $pdo->prepare("
        SELECT a.*, c.nom_principal as client_nom, c.commentaire as client_commentaire 
        FROM affaires a
        JOIN clients c ON a.client_id = c.id
        WHERE a.id = ?
    ");
    $stmt->execute([$affaire_id]);
    $affaire = $stmt->fetch();
    if (!$affaire) die("Affaire introuvable.");

    // 2. INFORMATIONS CLIENT COMPLÈTES
    $client_id = $affaire['client_id'];
    
    // Récupérer les infos client détaillées
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client_info = $stmt->fetch();
    
    // Récupérer les contacts
    $stmt = $pdo->prepare("SELECT * FROM client_contacts WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $client_contacts = $stmt->fetchAll();
    
    // Récupérer les adresses
    $stmt = $pdo->prepare("SELECT * FROM client_adresses WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $client_adresses = $stmt->fetchAll();
    
    // Récupérer les téléphones
    $stmt = $pdo->prepare("SELECT * FROM client_telephones WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $client_telephones = $stmt->fetchAll();
    
    // Récupérer les emails
    $stmt = $pdo->prepare("SELECT * FROM client_emails WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $client_emails = $stmt->fetchAll();

    // 3. LISTES DE BESOIN (Groupées par zone/date)
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(zone_chantier, 'Sans zone') as zone,
            DATE(date_creation) as date_liste,
            COUNT(*) as nb_lignes,
            MIN(date_creation) as date_creation,
            GROUP_CONCAT(DISTINCT statut) as statuts
        FROM besoins_lignes
        WHERE affaire_id = ?
        GROUP BY COALESCE(zone_chantier, 'Sans zone'), DATE(date_creation)
        ORDER BY date_creation DESC
    ");
    $stmt->execute([$affaire_id]);
    $besoins = $stmt->fetchAll();

    // 4. COMMANDES LIÉES
    $stmt = $pdo->prepare("
        SELECT c.*, f.nom as fournisseur_nom 
        FROM commandes_achats c
        JOIN fournisseurs f ON c.fournisseur_id = f.id
        WHERE c.affaire_id = ?
    ");
    $stmt->execute([$affaire_id]);
    $commandes_liees = $stmt->fetchAll();

    // 5. MATÉRIEL NÉCESSAIRE (Pense-bête)
    $stmt = $pdo->prepare("
        SELECT m.*, u.nom_complet as ajout_par
        FROM affaires_materiel m
        LEFT JOIN utilisateurs u ON m.user_id = u.id
        WHERE m.affaire_id = ?
        ORDER BY 
            FIELD(m.priorite, 'URGENTE', 'HAUTE', 'NORMALE', 'BASSE'),
            FIELD(m.statut, 'A_PREVOIR', 'COMMANDE', 'SUR_SITE', 'RETOURNE'),
            m.date_ajout DESC
    ");
    $stmt->execute([$affaire_id]);
    $materiel_liste = $stmt->fetchAll();

    // 6. TÂCHES LIÉES (Et sous-tâches pour JS)
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE affaire_id = ? ORDER BY status ASC, importance DESC, created_at DESC");
    $stmt->execute([$affaire_id]);
    $tasks_liees = $stmt->fetchAll();

    // Fetch Subtasks
    $subtasks_map = [];
    $task_ids = array_column($tasks_liees, 'id');
    if (!empty($task_ids)) {
        $placeholders = str_repeat('?,', count($task_ids) - 1) . '?';
        $stmt_sub = $pdo->prepare("SELECT * FROM task_items WHERE task_id IN ($placeholders)");
        $stmt_sub->execute($task_ids);
        $all_subs = $stmt_sub->fetchAll();
        foreach($all_subs as $s) {
            $subtasks_map[$s['task_id']][] = $s;
        }
    }

} catch (Exception $e) {
    die("Erreur SQL : " . $e->getMessage());
}

$page_title = 'Cockpit';
require_once 'header.php';
?>

<style>
/* APEX TRINITY - Remove ALL spacing from tab panes */
#cockpitContent .tab-pane {
    padding: 0 !important;
    margin: 0 !important;
}

#cockpitContent .tab-pane > .card {
    margin: 0 !important;
    border-radius: 0 !important;
}

#cockpitContent .card-header {
    border-radius: 0 !important;
}

/* Fix spacing between header and table */
#cockpitContent .table-responsive {
    margin: 0 !important;
    padding: 0 !important;
}

#cockpitContent .card > .table-responsive {
    margin-top: 0 !important;
}
</style>

    <div class="main-content">
        <div class="container-fluid mt-4">
            
            <!-- Fil d'ariane -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="affaires_liste.php">Affaires</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($affaire['nom_affaire']) ?></li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">
                        <i class="fas fa-folder text-warning me-2"></i><?= htmlspecialchars($affaire['nom_affaire'] ?? '') ?>
                    </h2>
                    <span class="text-muted small ms-1"><?= htmlspecialchars($affaire['client_nom'] ?? '') ?> | <?= htmlspecialchars($affaire['numero_prodevis'] ?? '') ?></span>
                </div>
                <?= badge_statut($affaire['statut']) ?>
            </div>

            <!-- TABS COCKPIT -->
            <ul class="nav nav-tabs mb-0 px-2 nav-tabs-mobile-scroll" id="cockpitTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold" id="infos-tab" data-bs-toggle="tab" data-bs-target="#infos" type="button">
                        <i class="fas fa-info-circle me-2"></i>Infos & Contacts
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="ged-tab" data-bs-toggle="tab" data-bs-target="#ged" type="button">
                        <i class="fas fa-file-alt me-2"></i>GED / Fichiers
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button">
                        <i class="fas fa-check-square me-2"></i>Tâches
                        <?php if (count($tasks_liees) > 0): ?>
                            <span class="badge bg-warning ms-2"><?= count($tasks_liees) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="materiel-tab" data-bs-toggle="tab" data-bs-target="#materiel" type="button">
                        <i class="fas fa-tools me-2"></i>Matériel Nécessaire
                        <?php if (count($materiel_liste) > 0): ?>
                            <span class="badge bg-secondary ms-2"><?= count($materiel_liste) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="planning-tab" data-bs-toggle="tab" data-bs-target="#planning" type="button">
                        <i class="fas fa-calendar-alt me-2"></i>Planning & Pose
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="besoins-tab" data-bs-toggle="tab" data-bs-target="#besoins" type="button">
                        <i class="fas fa-ruler-combined me-2"></i>Liste de Besoin
                        <?php if (count($besoins) > 0): ?>
                            <span class="badge bg-warning text-dark ms-2"><?= count($besoins) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="commandes-tab" data-bs-toggle="tab" data-bs-target="#commandes" type="button">
                        <i class="fas fa-shopping-cart me-2"></i>Commandes
                        <?php if (count($commandes_liees) > 0): ?>
                            <span class="badge bg-info ms-2"><?= count($commandes_liees) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="cockpitContent">
                
                <!-- TAB 1 : INFOS GENERALE -->
                <div class="tab-pane fade show active" id="infos">
                    <div class="card shadow-sm border-0 bg-dark text-white">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2"></i>Informations & Contacts</h5>
                        </div>
                        <div class="card-body p-2">
                            <div class="row">
                        <div class="col-md-6">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold"><i class="fas fa-user-circle me-2"></i>Détails Client</h6>
                                    <a href="clients_detail.php?id=<?= $client_id ?>" class="btn btn-sm btn-light text-primary rounded-pill">
                                        <i class="fas fa-external-link-alt me-1"></i>Fiche
                                    </a>
                                </div>
                                <div class="card-body">
                                    <?php if ($client_info): ?>
                                        <div class="mb-3 pb-3 border-bottom">
                                            <h5 class="mb-2">
                                                <i class="fas fa-user me-2 text-primary"></i>
                                                <?= htmlspecialchars($client_info['civilite'] ?? '') ?> 
                                                <strong><?= htmlspecialchars($client_info['nom_principal'] ?? '') ?></strong> 
                                                <?= htmlspecialchars($client_info['prenom'] ?? '') ?>
                                            </h5>
                                            <?php if (!empty($client_info['code_client'])): ?>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($client_info['code_client']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($client_info['adresse_postale'])): ?>
                                            <div class="mb-3">
                                                <small class="text-muted d-block mb-1"><i class="fas fa-map-marker-alt me-1"></i> Adresse</small>
                                                <p class="mb-0">
                                                    <?= nl2br(htmlspecialchars($client_info['adresse_postale'])) ?><br>
                                                    <strong><?= htmlspecialchars($client_info['code_postal'] ?? '') ?> <?= htmlspecialchars($client_info['ville'] ?? '') ?></strong>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex flex-column gap-2 mb-3">
                                            <?php if (!empty($client_info['telephone_mobile'])): ?>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <i class="fas fa-mobile-alt me-2 text-success"></i>
                                                        <a href="tel:<?= htmlspecialchars($client_info['telephone_mobile']) ?>" class="text-decoration-none fw-bold">
                                                            <?= htmlspecialchars($client_info['telephone_mobile']) ?>
                                                        </a>
                                                    </div>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="tel:<?= htmlspecialchars($client_info['telephone_mobile']) ?>" class="btn btn-outline-success" title="Appeler">
                                                            <i class="fas fa-phone"></i>
                                                        </a>
                                                        <a href="sms:<?= htmlspecialchars($client_info['telephone_mobile']) ?>" class="btn btn-outline-info" title="SMS">
                                                            <i class="fas fa-sms"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($client_info['telephone_fixe'])): ?>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <i class="fas fa-phone me-2 text-secondary"></i>
                                                        <a href="tel:<?= htmlspecialchars($client_info['telephone_fixe']) ?>" class="text-decoration-none">
                                                            <?= htmlspecialchars($client_info['telephone_fixe']) ?>
                                                        </a>
                                                    </div>
                                                    <a href="tel:<?= htmlspecialchars($client_info['telephone_fixe']) ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-phone"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($client_info['email_principal'])): ?>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div class="text-truncate" style="max-width: 200px;">
                                                        <i class="fas fa-envelope me-2 text-primary"></i>
                                                        <a href="mailto:<?= htmlspecialchars($client_info['email_principal']) ?>" class="text-decoration-none">
                                                            <?= htmlspecialchars($client_info['email_principal']) ?>
                                                        </a>
                                                    </div>
                                                    <a href="mailto:<?= htmlspecialchars($client_info['email_principal']) ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-envelope"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (count($client_contacts) > 0): ?>
                                            <hr>
                                            <h6 class="small fw-bold text-muted mb-2"><i class="fas fa-users me-1"></i> Contacts secondaires</h6>
                                            <ul class="list-unstyled small">
                                                <?php foreach($client_contacts as $contact): ?>
                                                    <li class="mb-2 p-2 bg-light rounded">
                                                        <div class="d-flex align-items-start justify-content-between">
                                                            <div>
                                                                <i class="fas fa-user-tie me-2 text-secondary"></i>
                                                                <strong><?= htmlspecialchars($contact['nom']) ?> <?= htmlspecialchars($contact['prenom'] ?? '') ?></strong>
                                                                <?php if ($contact['role']): ?>
                                                                    <br><span class="ms-4 text-muted fst-italic"><?= htmlspecialchars($contact['role']) ?></span>
                                                                <?php endif; ?>
                                                                <?php if ($contact['email']): ?>
                                                                    <br><span class="ms-4"><a href="mailto:<?= htmlspecialchars($contact['email']) ?>" class="text-muted"><?= htmlspecialchars($contact['email']) ?></a></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="text-muted">Aucune information client disponible.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- BLOC VENTE / PASSERELLE -->
                            <!-- BLOC VENTE / PASSERELLE -->
                            <div class="card shadow-sm border-0 h-100 mb-3">
                                <div class="card-header bg-primary text-white py-2">
                                    <h6 class="mb-0 fw-bold"><i class="fas fa-file-invoice-dollar me-2"></i>Infos Vente (Passerelle ProDevis)</h6>
                                </div>
                                <div class="card-body bg-light">
                                    <form method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_sales">
                                        
                                        <div class="mb-3">
                                            <label class="form-label small text-muted fw-bold">Désignation (Description Commerciale)</label>
                                            <input type="text" name="designation" class="form-control" 
                                                   value="<?= htmlspecialchars($affaire['designation'] ?? '') ?>" placeholder="Ex: Remplacement Menuiseries Bois...">
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label small text-muted fw-bold">Montant Vendu HT</label>
                                                <div class="input-group">
                                                    <input type="number" step="0.01" name="montant_ht" class="form-control fw-bold" 
                                                           value="<?= htmlspecialchars($affaire['montant_ht'] ?? 0) ?>">
                                                    <span class="input-group-text bg-white">€</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small text-muted fw-bold">Date Signature</label>
                                                <input type="date" name="date_signature" class="form-control" 
                                                       value="<?= htmlspecialchars($affaire['date_signature'] ?? '') ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small text-muted fw-bold">Statut Actuel</label>
                                            <select name="statut" class="form-select">
                                                <option value="Devis" <?= ($affaire['statut'] ?? '') == 'Devis' ? 'selected' : '' ?>>Devis (En cours)</option>
                                                <option value="Signé" <?= ($affaire['statut'] ?? '') == 'Signé' ? 'selected' : '' ?>>✅ Signé (Validé)</option>
                                                <option value="Terminé" <?= ($affaire['statut'] ?? '') == 'Terminé' ? 'selected' : '' ?>>Terminé (Clôturé)</option>
                                            </select>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-petrol btn-sm shadow-sm">
                                                <i class="fas fa-save me-2"></i>Mettre à jour & Valider
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Notes (Déplacé en dessous) -->
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-transparent fw-bold text-warning">Notes & Mémo</div>
                                <div class="card-body p-2">
                                    <textarea class="form-control border-0 bg-white" rows="3" placeholder="Notes..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2 : GED (IFRAME & UPLOAD) -->
                <div class="tab-pane fade" id="ged">
                    <div class="card shadow-sm border-0 bg-dark text-white">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-file-alt me-2"></i>GED / Fichiers</h5>
                        </div>
                        <div class="card-body p-2">
                            <?php $ged_path = $affaire['chemin_dossier_ged'] ?? ''; ?>
                            
                            <?php if(empty($ged_path) || !is_dir($ged_path)): ?>
                                <!-- CAS 1 : PAS DE DOSSIER -->
                                <div class="text-center py-5">
                                    <div class="mb-3 text-muted">
                                        <i class="fas fa-folder-minus fa-3x opacity-50"></i>
                                    </div>
                                    <h5>Aucun dossier GED activé</h5>
                                    <p class="text-secondary">Le dossier de stockage pour cette affaire n'existe pas encore.</p>
                                    
                                    <form action="ged_manager.php" method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="create_folder">
                                        <input type="hidden" name="affaire_id" value="<?= $affaire_id ?>">
                                        <button type="submit" class="btn btn-petrol rounded-pill shadow-sm">
                                            <i class="fas fa-folder-plus me-2"></i>Créer le dossier maintenant
                                        </button>
                                    </form>
                                    <div class="small text-muted mt-2">Emplacement : <?= GED_ROOT ?>/AFFAIRES/...</div>
                                </div>
                            
                            <?php else: ?>
                                <!-- CAS 2 : DOSSIER ACTIF -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0"><i class="fas fa-network-wired me-2"></i>Dossier Réseau : <small class="text-muted font-monospace"><?= htmlspecialchars($ged_path) ?></small></h6>
                                    
                                    <!-- UPLOAD BUTTON -->
                                    <label class="btn btn-outline-dark btn-sm rounded-pill">
                                        <i class="fas fa-cloud-upload-alt me-2"></i>Uploader un fichier
                                        <input type="file" id="gedUploadInput" hidden onchange="uploadGedFile()">
                                    </label>
                                </div>
                                
                                <div class="progress mb-3 d-none" id="uploadProgress" style="height: 5px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                                </div>

                                <!-- IFRAME VERS VIEW.PHP -->
                                <?php 
                                    $iframe_src = "view.php?path=" . urlencode($ged_path);
                                ?>
                                <iframe id="gedFrame" src="<?= $iframe_src ?>" style="width:100%; height:600px; border:1px solid #dee2e6; border-radius:4px;"></iframe>
                                
                                <script>
                                    function uploadGedFile() {
                                        const input = document.getElementById('gedUploadInput');
                                        const file = input.files[0];
                                        if (!file) return;

                                        const formData = new FormData();
                                        formData.append('file', file);
                                        formData.append('target_dir', <?= json_encode($ged_path) ?>);

                                        // UI Loading
                                        document.getElementById('uploadProgress').classList.remove('d-none');
                                        
                                        fetch('ged_upload.php', {
                                            method: 'POST',
                                            body: formData
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                            if(data.success) {
                                                // Reload Iframe
                                                document.getElementById('gedFrame').contentWindow.location.reload();
                                            } else {
                                                alert("Erreur : " + data.error);
                                            }
                                        })
                                        .catch(err => {
                                            console.error(err);
                                            alert("Erreur réseau");
                                        })
                                        .finally(() => {
                                            document.getElementById('uploadProgress').classList.add('d-none');
                                            input.value = ''; // Reset
                                        });
                                    }
                                </script>
                            <?php endif; ?>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3 : TÂCHES (Nouvelle Interface Split View) -->
                <div class="tab-pane fade" id="tasks">
                    <div class="card shadow-sm border-0 bg-dark text-white" style="height: 600px;">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-check-square me-2"></i>Tâches & Sous-tâches</h5>
                            <button class="btn btn-sm btn-light fw-bold text-primary rounded-pill" onclick="openAddTaskModal()">
                                <i class="fas fa-plus me-2"></i>Nouvelle Tâche
                            </button>
                        </div>
                        <div class="card-body p-0 d-flex bg-light text-dark h-100">
                             
                            <!-- LEFT: TASK LIST -->
                            <div class="col-md-5 border-end overflow-auto h-100 bg-white">
                                <?php if(empty($tasks_liees)): ?>
                                    <div class="p-4 text-center text-muted mt-5">
                                        <i class="fas fa-clipboard-check fa-3x mb-3 opacity-25"></i>
                                        <h6>Aucune tâche</h6>
                                        <p class="small">Utilisez "Nouvelle Tâche" pour organiser ce chantier.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach($tasks_liees as $t): ?>
                                            <?php 
                                                $nb_subs = count($subtasks_map[$t['id']] ?? []);
                                                $nb_done = 0;
                                                if($nb_subs > 0) {
                                                    $nb_done = count(array_filter($subtasks_map[$t['id']], fn($s) => $s['is_completed']));
                                                }
                                                $is_completed = ($t['status'] == 'done');
                                                $item_class = $is_completed ? 'bg-light text-muted' : '';
                                            ?>
                                            <div class="list-group-item list-group-item-action p-3 task-row <?= $item_class ?>" 
                                                 onclick="showDetail(this, <?= $t['id'] ?>)"
                                                 style="cursor: pointer; border-left: 4px solid transparent;">
                                                
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <span class="fw-bold <?= $is_completed ? 'text-decoration-line-through' : 'text-dark' ?>">
                                                        <?= htmlspecialchars($t['title']) ?>
                                                    </span>
                                                    <?php if($t['importance'] == 'high'): ?>
                                                        <span class="badge bg-danger ms-2">!</span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between align-items-center small text-muted">
                                                    <div>
                                                        <?php 
                                                            $nb_subs = count($subtasks_map[$t['id']] ?? []);
                                                            // Logic matches tasks.js: unchecked = total - completed
                                                            $nb_done = 0;
                                                            if($nb_subs > 0) {
                                                                $nb_done = count(array_filter($subtasks_map[$t['id']], fn($s) => $s['is_completed']));
                                                            }
                                                            $nb_unchecked = $nb_subs - $nb_done;
                                                            $badge_display = ($nb_subs > 0) ? 'inline-block' : 'none';
                                                        ?>
                                                        <span class="badge bg-secondary rounded-pill" id="badge-count-<?= $t['id'] ?>" style="display: <?= $badge_display ?>">
                                                            <i class="fas fa-list-ul me-1"></i> <?= $nb_unchecked ?>/<?= $nb_subs ?>
                                                        </span>
                                                    </div>
                                                    <div><?= date('d/m', strtotime($t['created_at'])) ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- RIGHT: DETAILS PANEL -->
                            <div class="col-md-7 h-100 overflow-auto bg-body-tertiary">
                                <div id="detail-panel" class="p-4 h-100">
                                    <div class="d-flex flex-column justify-content-center align-items-center h-100 text-muted opacity-50">
                                        <i class="fas fa-hand-pointer fa-3x mb-3"></i>
                                        <h5>Sélectionnez une tâche</h5>
                                        <p>Les détails et sous-tâches apparaîtront ici.</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>



                <!-- TAB 4 : MATÉRIEL NÉCESSAIRE -->
                <div class="tab-pane fade" id="materiel">
                    <div class="card shadow-sm border-0 bg-dark text-white">
                        <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-tools me-2"></i>Matériel Nécessaire</h5>
                            <button class="btn btn-sm btn-success fw-bold rounded-pill" onclick="MaterielAffaire.openAdd()">
                                <i class="fas fa-plus me-2"></i>Ajouter Matériel
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-ag-theme table-mobile-cards">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-white">Matériel</th>
                                        <th class="text-white">Priorité</th>
                                        <th class="text-white">Statut</th>
                                        <th class="text-white">Commentaire</th>
                                        <th class="text-white text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($materiel_liste) > 0): ?>
                                        <?php foreach($materiel_liste as $mat): ?>
                                        <tr>
                                            <td data-label="Matériel">
                                                <strong><?= htmlspecialchars($mat['designation']) ?></strong>
                                                <div class="small text-muted">
                                                    <?= $mat['quantite'] ?> <?= htmlspecialchars($mat['unite']) ?>
                                                </div>
                                            </td>
                                            <td data-label="Priorité">
                                                <?php
                                                $prio_class = match($mat['priorite']) {
                                                    'URGENTE' => 'bg-danger',
                                                    'HAUTE' => 'bg-warning text-dark border border-danger',
                                                    'NORMALE' => 'bg-warning text-dark',
                                                    'BASSE' => 'bg-secondary',
                                                    default => 'bg-secondary'
                                                };
                                                ?>
                                                <span class="badge <?= $prio_class ?>"><?= $mat['priorite'] ?></span>
                                            </td>
                                            <td data-label="Statut">
                                                <div class="dropdown">
                                                    <?php
                                                    $statut_class = match($mat['statut']) {
                                                        'A_PREVOIR' => 'bg-secondary',
                                                        'COMMANDE' => 'bg-info',
                                                        'SUR_SITE' => 'bg-success',
                                                        'RETOURNE' => 'bg-dark',
                                                        default => 'bg-secondary'
                                                    };
                                                    $statut_icon = match($mat['statut']) {
                                                        'A_PREVOIR' => 'fa-hourglass-start',
                                                        'COMMANDE' => 'fa-shopping-cart',
                                                        'SUR_SITE' => 'fa-check',
                                                        'RETOURNE' => 'fa-undo',
                                                        default => 'fa-question'
                                                    };
                                                    ?>
                                                    <button class="btn btn-sm badge <?= $statut_class ?> dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas <?= $statut_icon ?> me-1"></i> <?= str_replace('_', ' ', $mat['statut']) ?>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-dark">
                                                        <li><a class="dropdown-item" href="#" onclick="MaterielAffaire.changeStatus(<?= $mat['id'] ?>, 'A_PREVOIR')"><i class="fas fa-hourglass-start me-2 text-secondary"></i>À PRÉVOIR</a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="MaterielAffaire.changeStatus(<?= $mat['id'] ?>, 'COMMANDE')"><i class="fas fa-shopping-cart me-2 text-info"></i>COMMANDÉ</a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="MaterielAffaire.changeStatus(<?= $mat['id'] ?>, 'SUR_SITE')"><i class="fas fa-check me-2 text-success"></i>SUR SITE</a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="MaterielAffaire.changeStatus(<?= $mat['id'] ?>, 'RETOURNE')"><i class="fas fa-undo me-2 text-muted"></i>RETOURNE</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                            <td data-label="Commentaire" class="text-break">
                                                <?= nl2br(htmlspecialchars($mat['commentaire'] ?? '')) ?>
                                                <?php if ($mat['ajout_par']): ?>
                                                    <div class="text-muted extra-small mt-1">
                                                        <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($mat['ajout_par']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Actions" class="text-end">
                                                <button class="btn btn-outline-light btn-sm me-1" onclick="MaterielAffaire.openEdit(<?= $mat['id'] ?>)" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm" onclick="MaterielAffaire.delete(<?= $mat['id'] ?>)" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-5">
                                                <i class="fas fa-tools fa-3x mb-3 opacity-50"></i>
                                                <h6 class="mb-2">Aucun matériel listé</h6>
                                                <p class="small mb-0">Ajoutez le matériel spécifique nécessaire pour ce chantier (pelle, béton, aspirateur...).</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>



                <!-- TAB 6 : PLANNING -->
                <div class="tab-pane fade" id="planning">
                    <div class="card shadow-sm border-0 bg-dark text-white">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-alt me-2"></i>Planning & Pose</h5>
                        </div>
                        <div class="card-body p-2">
                     <div class="row">
                        <div class="col-md-6">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header bg-primary text-white py-2">
                                    <h6 class="mb-0 fw-bold"><i class="fas fa-calendar-day me-2"></i>Planification Intervention</h6>
                                </div>
                                <div class="card-body bg-light">
                                    <form method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="update_planning" value="1">
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted">Statut du Chantier / Pose</label>
                                            <select name="statut_chantier" class="form-select">
                                                <?php 
                                                $statuts_pose = ['A Planifier', 'Planifié', 'En Cours', 'Terminé', 'Facturé'];
                                                foreach($statuts_pose as $sp): ?>
                                                    <option value="<?= $sp ?>" <?= ($affaire['statut_chantier'] ?? 'A Planifier') == $sp ? 'selected' : '' ?>>
                                                        <?= $sp ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label small text-muted fw-bold">Date Début</label>
                                                <input type="date" name="date_pose_debut" class="form-control" value="<?= $affaire['date_pose_debut'] ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small text-muted fw-bold">Date Fin</label>
                                                <input type="date" name="date_pose_fin" class="form-control" value="<?= $affaire['date_pose_fin'] ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small text-muted fw-bold">Équipe / Intervenants</label>
                                            <input type="text" name="equipe_pose" class="form-control" placeholder="Ex: Equipe 1, Sous-Traitant X..." value="<?= htmlspecialchars($affaire['equipe_pose'] ?? '') ?>">
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-petrol shadow-sm rounded-pill">
                                                <i class="fas fa-save me-2"></i>Enregistrer le Planning
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                             <div class="alert alert-light border shadow-sm h-100">
                                <h5><i class="fas fa-info-circle me-2"></i>Gestion des Poses</h5>
                                <p>Définissez ici les dates d'intervention pour que ce chantier apparaisse dans le <strong>Planning Global</strong>.</p>
                                <ul>
                                    <li><strong>A Planifier</strong> : En attente de date.</li>
                                    <li><strong>Planifié</strong> : Dates validées avec le client.</li>
                                    <li><strong>Terminé</strong> : Prêt pour facturation.</li>
                                </ul>
                             </div>
                        </div>
                     </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 6 : LISTES DE BESOIN -->
                <div class="tab-pane fade" id="besoins">
                    <div class="card shadow-sm border-0 bg-dark text-white">
                        <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-clipboard-list me-2"></i>Listes de Besoin</h5>
                            <a href="besoins_saisie_v2.php?id=<?= $affaire_id ?>" class="btn btn-sm btn-success fw-bold rounded-pill">
                                <i class="fas fa-plus me-2"></i>Nouvelle Liste
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-ag-theme table-mobile-cards">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-white">Zone</th>
                                        <th class="text-white">Date de création</th>
                                        <th class="text-white text-center">Nb lignes</th>
                                        <th class="text-white text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($besoins) > 0): ?>
                                        <?php foreach($besoins as $liste): ?>
                                        <tr>
                                            <td data-label="Zone">
                                                <span class="badge bg-info"><?= htmlspecialchars($liste['zone']) ?></span>
                                            </td>
                                            <td data-label="Date">
                                                <i class="fas fa-calendar me-2 text-muted"></i>
                                                <?= date('d/m/Y à H:i', strtotime($liste['date_creation'])) ?>
                                            </td>
                                            <td data-label="Nb lignes" class="text-center">
                                                <span class="badge bg-dark"><?= $liste['nb_lignes'] ?></span>
                                            </td>
                                            <td data-label="Actions" class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="besoins_saisie_v2.php?id=<?= $affaire_id ?>" 
                                                       class="btn btn-outline-light" 
                                                       title="Ouvrir et consulter">
                                                        <i class="fas fa-folder-open me-1"></i> Ouvrir
                                                    </a>
                                                    <button class="btn btn-outline-light" 
                                                            onclick="window.print()" 
                                                            title="Imprimer">
                                                        <i class="fas fa-print"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-5">
                                                <i class="fas fa-clipboard fa-3x mb-3 opacity-50"></i>
                                                <h6 class="mb-2">Aucune liste de besoin</h6>
                                                <p class="small mb-0">Cliquez sur "Nouvelle Liste" pour créer une liste de besoin pour cette affaire.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 7 : COMMANDES -->
                <div class="tab-pane fade" id="commandes">
                    <div class="card shadow-sm border-0 bg-dark text-white">
                        <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-shopping-cart me-2"></i>Commandes Fournisseurs</h5>
                            <div class="dropdown">
                                <button class="btn btn-light text-primary fw-bold btn-sm dropdown-toggle rounded-pill shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-plus-circle me-2"></i>Créer Commande
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                                    <li>
                                        <a class="dropdown-item py-2" href="gestion_commande_rapide.php?affaire_id=<?= $affaire_id ?>">
                                            <i class="fas fa-rocket me-2 text-danger"></i>Commande Rapide
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item py-2" href="commandes_saisie.php?affaire_id=<?= $affaire_id ?>">
                                            <i class="fas fa-file-contract me-2 text-primary"></i>Commande Normale
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-ag-theme table-mobile-cards">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-white">Référence</th>
                                        <th class="text-white">Fournisseur</th>
                                        <th class="text-white">Statut</th>
                                        <th class="text-center text-white">En Attente</th>
                                        <th class="text-center text-white">Commandé</th>
                                        <th class="text-center text-white">ARC Reçu</th>
                                        <th class="text-center text-white">Livraison Prévue</th>
                                        <th class="text-center text-white">Livraison Réelle</th>
                                        <th class="text-white">Total HT</th>
                                        <th class="text-white">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($commandes_liees as $cmd): ?>
                                    <tr>
                                        <td data-label="Référence" class="fw-bold"><?= htmlspecialchars($cmd['ref_interne']) ?></td>
                                        <td data-label="Fournisseur"><?= htmlspecialchars($cmd['fournisseur_nom']) ?></td>
                                        <td data-label="Statut"><?= badge_statut($cmd['statut']) ?></td>
                                        
                                        <!-- Date En Attente -->
                                        <td data-label="En Attente" class="text-center">
                                            <?php if ($cmd['date_en_attente']): ?>
                                                <i class="fas fa-check-circle text-success" title="<?= date('d/m/Y', strtotime($cmd['date_en_attente'])) ?>"></i>
                                                <small class="d-block text-muted"><?= date('d/m', strtotime($cmd['date_en_attente'])) ?></small>
                                            <?php else: ?>
                                                <i class="fas fa-circle text-secondary opacity-25"></i>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Date Commande -->
                                        <td data-label="Commandé" class="text-center">
                                            <?php if ($cmd['date_commande']): ?>
                                                <i class="fas fa-check-circle text-success" title="<?= date('d/m/Y', strtotime($cmd['date_commande'])) ?>"></i>
                                                <small class="d-block text-muted"><?= date('d/m', strtotime($cmd['date_commande'])) ?></small>
                                            <?php else: ?>
                                                <i class="fas fa-circle text-secondary opacity-25"></i>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Date ARC Reçu -->
                                        <td data-label="ARC Reçu" class="text-center">
                                            <?php if ($cmd['date_arc_recu']): ?>
                                                <i class="fas fa-check-circle text-success" title="<?= date('d/m/Y', strtotime($cmd['date_arc_recu'])) ?>"></i>
                                                <small class="d-block text-muted"><?= date('d/m', strtotime($cmd['date_arc_recu'])) ?></small>
                                            <?php else: ?>
                                                <i class="fas fa-circle text-warning opacity-50" title="En attente ARC"></i>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Date Livraison Prévue -->
                                        <td data-label="Livraison Prévue" class="text-center">
                                            <?php if ($cmd['date_livraison_prevue']): ?>
                                                <?php
                                                $jours_restants = floor((strtotime($cmd['date_livraison_prevue']) - time()) / 86400);
                                                $is_retard = $jours_restants < 0;
                                                $is_urgent = $jours_restants >= 0 && $jours_restants <= 3;
                                                ?>
                                                <i class="fas fa-calendar-alt <?= $is_retard ? 'text-danger' : ($is_urgent ? 'text-warning' : 'text-info') ?>" 
                                                   title="<?= date('d/m/Y', strtotime($cmd['date_livraison_prevue'])) ?>"></i>
                                                <small class="d-block <?= $is_retard ? 'text-danger fw-bold' : 'text-muted' ?>">
                                                    <?= date('d/m', strtotime($cmd['date_livraison_prevue'])) ?>
                                                    <?php if ($is_retard): ?>
                                                        <br><span class="badge bg-danger">-<?= abs($jours_restants) ?>j</span>
                                                    <?php elseif ($is_urgent): ?>
                                                        <br><span class="badge bg-warning text-dark">J-<?= $jours_restants ?></span>
                                                    <?php endif; ?>
                                                </small>
                                            <?php else: ?>
                                                <i class="fas fa-exclamation-triangle text-danger" title="Date non définie"></i>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Date Livraison Réelle -->
                                        <td data-label="Livraison Réelle" class="text-center">
                                            <?php if ($cmd['date_livraison_reelle']): ?>
                                                <i class="fas fa-check-circle text-success" title="Livré le <?= date('d/m/Y', strtotime($cmd['date_livraison_reelle'])) ?>"></i>
                                                <small class="d-block text-success fw-bold"><?= date('d/m', strtotime($cmd['date_livraison_reelle'])) ?></small>
                                            <?php else: ?>
                                                <i class="fas fa-hourglass-half text-info opacity-50" title="En attente de livraison"></i>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td data-label="Total HT"><?= number_format($cmd['montant_total_ht'] ?? 0, 2, ',', ' ') ?> €</td>
                                        <td data-label="Action"><a href="commandes_detail.php?id=<?= $cmd['id'] ?>" class="btn btn-sm btn-outline-light rounded-pill">Voir</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                </div>

                <!-- TAB 8 : MATERIEL -->
                <div class="tab-pane fade" id="materiel">
                     <div class="card shadow-sm border-0 bg-dark text-white">
                        <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-dolly-flatbed me-2"></i>Matériel Nécessaire au Chantier</h5>
                            <a href="stocks_sortie.php" class="btn btn-light text-primary rounded-pill btn-sm">
                                <i class="fas fa-plus me-2"></i>Nouvelle Sortie
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Référence</th>
                                        <th>Désignation</th>
                                        <th>Qté</th>
                                        <th>Coût Estimé</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_materiel = 0; 
                                    foreach($materiel_sortie as $m): 
                                        $cout = $m['quantite'] * ($m['prix_achat_actuel'] ?? 0);
                                        $total_materiel += $cout;
                                    ?>
                                    <tr>
                                        <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($m['date_mouvement'])) ?></td>
                                        <td class="font-monospace fw-bold text-primary"><?= h($m['ref_fournisseur']) ?></td>
                                        <td>
                                            <?= h($m['designation_commerciale']) ?>
                                            <div class="small text-muted"><?= h($m['nom_couleur']) ?></div>
                                        </td>
                                        <td class="fw-bold fs-5"><?= $m['quantite'] + 0 ?></td>
                                        <td class="text-muted"><?= number_format($cout, 2) ?> €</td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if(empty($materiel_sortie)): ?>
                                        <tr><td colspan="5" class="text-center py-5 text-muted">Aucun matériel sorti pour ce chantier.</td></tr>
                                    <?php else: ?>
                                        <tr class="table-light fw-bold">
                                            <td colspan="4" class="text-end">TOTAL MATÉRIEL :</td>
                                            <td class="text-dark"><?= number_format($total_materiel, 2) ?> €</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                     </div>
                </div>



    <!-- MODAL MATÉRIEL -->
    <div class="modal fade" id="modalMateriel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalMaterielLabel">Ajouter Matériel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formMateriel">
                        <input type="hidden" id="materiel_id" name="id">
                        <input type="hidden" id="materiel_affaire_id" name="affaire_id" value="<?= $affaire_id ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Désignation</label>
                            <input type="text" class="form-control" id="materiel_designation" name="designation" required placeholder="Ex: Pelle, Béton, Aspirateur...">
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">Quantité</label>
                                <input type="number" class="form-control" id="materiel_quantite" name="quantite" value="1" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Unité</label>
                                <select class="form-select" id="materiel_unite" name="unite">
                                    <option value="unité">Unité</option>
                                    <option value="sac">Sac</option>
                                    <option value="kg">Kg</option>
                                    <option value="m²">m²</option>
                                    <option value="m³">m³</option>
                                    <option value="litre">Litre</option>
                                    <option value="rouleau">Rouleau</option>
                                    <option value="boîte">Boîte</option>
                                    <option value="paquet">Paquet</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">Priorité</label>
                                <select class="form-select" id="materiel_priorite" name="priorite">
                                    <option value="BASSE">⚪ Basse</option>
                                    <option value="NORMALE" selected>🟡 Normale</option>
                                    <option value="HAUTE">🟠 Haute</option>
                                    <option value="URGENTE">🔴 Urgente</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Statut</label>
                                <select class="form-select" id="materiel_statut" name="statut">
                                    <option value="A_PREVOIR" selected>⏳ À prévoir</option>
                                    <option value="COMMANDE">🛒 Commandé</option>
                                    <option value="SUR_SITE">✅ Sur site</option>
                                    <option value="RETOURNE">↩️ Retourné</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Commentaire</label>
                            <textarea class="form-control" id="materiel_commentaire" name="commentaire" rows="2" placeholder="Précisions utiles..."></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary fw-bold px-4">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->

    <!-- MODAL ADD TASK (Copie adaptée de tasks.php) -->
    <div class="modal fade" id="addTaskModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Nouvelle Tâche</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="tasks.php" method="POST">
                    <div class="modal-body bg-white">
                        <input type="hidden" name="action" value="add_task">
                        <!-- Force l'affaire courante -->
                        <input type="hidden" name="context_type" value="affaire">
                        <input type="hidden" name="affaire_id" value="<?= $affaire_id ?>">
                        <!-- Redirect back here -->
                        <input type="hidden" name="redirect" value="affaires_detail.php?id=<?= $affaire_id ?>#tasks">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Titre</label>
                            <input type="text" name="title" class="form-control" required placeholder="Ex: Vérifier métrés...">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Priorité</label>
                                <select name="importance" class="form-select">
                                    <option value="normal">Normale</option>
                                    <option value="high">Urgent 🚨</option>
                                    <option value="low">Faible ☕</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date Création</label>
                                <input type="date" name="task_date" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description / Notes</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Détails..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Sous-tâches (une par ligne)</label>
                            <textarea name="subtasks_text" class="form-control" rows="3" placeholder="- Etape 1&#10;- Etape 2..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary px-4">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- TASKS JS INJECTION -->
    <script>
        window.CSRF_TOKEN = "<?= csrf_token() ?>";
        window.tasksStore = {};
        
        <?php 
        // Préparer les données pour JS
        $js_tasks_data = [];
        foreach($tasks_liees as $t) {
            $t_copy = $t;
            $t_copy['subtasks'] = $subtasks_map[$t['id']] ?? [];
            // Champs nécessaires pour display
            $t_copy['nom_affaire'] = $affaire['nom_affaire']; 
            $t_copy['ref_interne'] = ''; 
            $js_tasks_data[$t['id']] = $t_copy;
        }
        ?>
        
        const injectedTasks = <?= json_encode($js_tasks_data) ?>;
        if(injectedTasks) {
            window.tasksStore = injectedTasks;
        }
        
        // Override global function for this view if needed
        // Mais tasks.js est assez générique.
    </script>
    <script src="assets/js/tasks.js?v=<?= time() ?>"></script>
    <script src="assets/js/modules/materiel_affaire.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            MaterielAffaire.init(<?= $affaire_id ?>);
        });
    </script>

    <script>
        // Gestion de l'URL hash pour ouvrir le bon onglet directement
        document.addEventListener("DOMContentLoaded", function() {
            var hash = window.location.hash;
            if (hash) {
                var triggerEl = document.querySelector('#cockpitTabs button[data-bs-target="' + hash + '"]');
                if (triggerEl) {
                    var tab = new bootstrap.Tab(triggerEl);
                    tab.show();
                }
            }
        });
    </script>
</body>
</html>

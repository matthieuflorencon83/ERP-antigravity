<?php
// DEBUG: OFF
// ini_set('display_errors', 0); // En prod

require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';



// --- AJAX/POST HANDLERS (Subtasks) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    // CSRF Check (ACTIVÉ - Sécurité Critique)
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'error' => 'CSRF token invalide']));
    } 

    // Nettoyage buffer pour garantir un JSON valide
    if (ob_get_length()) ob_clean();

    header('Content-Type: application/json');
    $res = ['success' => false];


    try {
        if ($_POST['ajax_action'] === 'add_sub') {
            $stmt = $pdo->prepare("INSERT INTO task_items (task_id, content, is_completed) VALUES (?, ?, 0)");
            $stmt->execute([$_POST['task_id'], $_POST['content']]);
            $res = ['success' => true, 'id' => $pdo->lastInsertId()];
        }
        elseif ($_POST['ajax_action'] === 'toggle_sub') {
            $stmt = $pdo->prepare("UPDATE task_items SET is_completed = NOT is_completed WHERE id=?");
            $stmt->execute([$_POST['sub_id']]);
            $res = ['success' => true];
        }
        elseif ($_POST['ajax_action'] === 'edit_sub') {
            $stmt = $pdo->prepare("UPDATE task_items SET content=? WHERE id=?");
            $stmt->execute([$_POST['content'], $_POST['sub_id']]);
            $res = ['success' => true];
        }
        elseif ($_POST['ajax_action'] === 'del_sub') {
            $stmt = $pdo->prepare("DELETE FROM task_items WHERE id=?");
            $stmt->execute([$_POST['sub_id']]);
            $res = ['success' => true];
        }
        elseif ($_POST['ajax_action'] === 'update_task_desc') {
            $stmt = $pdo->prepare("UPDATE tasks SET description=? WHERE id=?");
            $stmt->execute([$_POST['description'], $_POST['task_id']]);
            $res = ['success' => true];
        }
    } catch (Exception $e) {
        $res['error'] = $e->getMessage();
    }

    echo json_encode($res);
    exit;
}

// --- ACTIONS PHP (PLACÉES AU DÉBUT POUR REDIRECTION PROPRE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DEBUG TEMPORAIRE
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $action = $_POST['action'] ?? '';
    
    // NETTOYAGE DES DONNEES
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $imp = $_POST['importance'];
    // $due = !empty($_POST['due_date']) ? $_POST['due_date'] : null; // REMPLACÉ PAR CREATED_AT
    $created_date = !empty($_POST['task_date']) ? $_POST['task_date'] : date('Y-m-d');
    $created_date = !empty($_POST['task_date']) ? $_POST['task_date'] : date('Y-m-d');
    
    // LOGIQUE CONTEXTE (EXCLUSION MUTUELLE)
    $context_type = $_POST['context_type'] ?? 'none';
    $aff = ($context_type === 'affaire' && !empty($_POST['affaire_id'])) ? $_POST['affaire_id'] : null;
    $cmd = ($context_type === 'commande' && !empty($_POST['commande_id'])) ? $_POST['commande_id'] : null;

    $user_id = $_SESSION['user_id'];

    try {
    if ($action === 'add_task') {
        // On utilise la date saisie pour created_at (avec l'heure actuelle ou 00:00)
        $final_created_at = $created_date . ' ' . date('H:i:s');
        
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, importance, due_date, status, affaire_id, commande_id, created_at) VALUES (?, ?, ?, ?, NULL, 'todo', ?, ?, ?)");
        $stmt->execute([$user_id, $title, $desc, $imp, $aff, $cmd, $final_created_at]);
        $new_task_id = $pdo->lastInsertId();

        // GESTION DES SOUS-TÂCHES (TEXTAREA)
        if (!empty($_POST['subtasks_text'])) {
            $lines = explode("\n", $_POST['subtasks_text']);
            $stmt_sub = $pdo->prepare("INSERT INTO task_items (task_id, content, is_completed) VALUES (?, ?, 0)");
            foreach($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $stmt_sub->execute([$new_task_id, $line]);
                }
            }
        }
    }
    elseif ($action === 'edit_task') {
        $task_id = $_POST['task_id'];
        // On met à jour la date de création
        $final_created_at = $created_date . ' ' . date('H:i:s'); 
        
        $stmt = $pdo->prepare("UPDATE tasks SET title=?, description=?, importance=?, created_at=?, affaire_id=?, commande_id=? WHERE id=?");
        $stmt->execute([$title, $desc, $imp, $final_created_at, $aff, $cmd, $task_id]);

        // SOUS-TACHES (AJOUT SEULEMENT EN EDIT)
        if (!empty($_POST['subtasks_text'])) {
            $lines = explode("\n", $_POST['subtasks_text']);
            $stmt_sub = $pdo->prepare("INSERT INTO task_items (task_id, content, is_completed) VALUES (?, ?, 0)");
            foreach($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $stmt_sub->execute([$task_id, $line]);
                }
            }
        }
    }
    } catch (Exception $e) {
        die("ERREUR SQL : " . $e->getMessage());
    }
    
    // REDIRECTION DYNAMIQUE
    $redirect = $_POST['redirect'] ?? 'tasks.php';
    header("Location: $redirect");
    exit;
}


// TOGGLES / DELETE (Main Tasks via GET - kept for compatibility/links)
// (AJAX Handler moved to top)

if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE tasks SET status = IF(status='done','todo','done') WHERE id=?")->execute([$_GET['toggle']]);
    $redirect = $_GET['redirect'] ?? 'tasks.php';
    header("Location: $redirect"); exit;
}
if (isset($_GET['del']) || isset($_GET['delete_id'])) {
    $id = $_GET['del'] ?? $_GET['delete_id'];
    $pdo->prepare("DELETE FROM tasks WHERE id=?")->execute([$id]);
    $redirect = $_GET['redirect'] ?? 'tasks.php';
    header("Location: $redirect"); exit;
}

// --- DATA FETCHING ---
$tasks = [];
$tasks_todo = [];
$tasks_done = [];
$subtasks_map = [];
$affaires_list = [];
$commandes_list = [];

try {
    // Récupérer les tâches avec info Affaire/Commande
    $sql = "SELECT t.*, a.nom_affaire, c.ref_interne 
            FROM tasks t 
            LEFT JOIN affaires a ON t.affaire_id = a.id 
            LEFT JOIN commandes_achats c ON t.commande_id = c.id
            WHERE t.user_id = ? 
            ORDER BY t.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $tasks = $stmt->fetchAll();

    // Séparation En cours / Terminées
    foreach($tasks as $t) {
        if ($t['status'] === 'done') $tasks_done[] = $t;
        else $tasks_todo[] = $t;
    }

    // Récupérer les sous-tâches
    $task_ids = array_column($tasks, 'id');
    if (!empty($task_ids)) {
        $placeholders = str_repeat('?,', count($task_ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT * FROM task_items WHERE task_id IN ($placeholders)");
        $stmt->execute($task_ids);
        $all = $stmt->fetchAll();
        foreach($all as $i) $subtasks_map[$i['task_id']][] = $i;
    }

    // Récupérer data pour filtres
    try {
        $stmt_aff = $pdo->query("SELECT id, nom_affaire FROM affaires ORDER BY nom_affaire ASC");
        $affaires_list = $stmt_aff->fetchAll();
        
        $stmt_cmd = $pdo->query("SELECT id, ref_interne FROM commandes_achats ORDER BY ref_interne ASC");
        $commandes_list = $stmt_cmd->fetchAll();
    } catch (Exception $e) {
        // Ignore filter errors
        error_log("Filter Load Error: " . $e->getMessage());
    }

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>ERREUR SQL CRITIQUE: " . $e->getMessage() . "</div>";
    // We still allow page to load to see the error
}

$page_title = "To Do List"; // Titre dans le bandeau
require_once 'header.php'; // RÉACTIVATION DU VRAI HEADER
?>

<!-- Select2 CSS (Required for Filter Dropdown) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    /* Custom style to make Select2 fit in the header */
    .select2-container--bootstrap-5 .select2-selection {
        border: none;
        background-color: transparent;
        font-weight: bold;
        color: #6c757d; /* text-muted */
        font-size: 0.875rem; /* small */
        padding-left: 0;
    }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        color: #6c757d;
        padding-left: 0;
    }
    .select2-container .select2-selection--single .select2-selection__arrow {
        display: none; /* Hide default arrow to look like text */
    }
    /* OVERRIDE GLOBAL NAV LINK STYLES FOR ABSOLUTE TAB VISIBILITY */
    #taskTabs .nav-link {
        color: #495057 !important; /* Standard Bootstrap gray */
    }
    #taskTabs .nav-link.active {
        color: #0d6efd !important; /* Bootstrap Primary Blue */
    }
    
    /* ELIMINATE PAGE SCROLL */
    html, body {
        overflow: hidden !important;
        height: 100vh !important;
    }


</style>

<!-- FIX LAYOUT : On enlève les fermetures manuelles risquées -->
<!-- On pousse le contenu vers le bas pour éviter le header fixe -->

<div id="real-content" class="bg-body position-relative" style="z-index: 10; height: calc(100vh - 60px); margin-top: -60px; padding-top: 20px; border-radius: 15px 15px 0 0; overflow: hidden;">
    <!-- CONTAINER PRINCIPAL -->
    <div class="container-fluid px-4">
        
        <!-- OPÉRATIONS (BOUTON CRÉATION) -->
        <div class="d-flex justify-content-end align-items-center mb-3">
            <button class="btn btn-primary shadow-sm" onclick="openAddTaskModal()">
                <i class="fas fa-plus me-2"></i>Nouvelle Tâche
            </button>
        </div>

        <!-- TABS -->
        <ul class="nav nav-tabs mb-3 border-bottom-0" id="taskTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-bold border" id="todo-tab" data-bs-toggle="tab" data-bs-target="#todo-pane">
                    <i class="fas fa-list-ul me-2"></i>EN COURS <span class="badge bg-primary ms-2"><?= count($tasks_todo) ?></span>
                </button>
            </li>
            <li class="nav-item ms-2">
                <button class="nav-link fw-bold border" id="done-tab" data-bs-toggle="tab" data-bs-target="#done-pane">
                    <i class="fas fa-check me-2"></i>TERMINÉES <span class="badge bg-secondary ms-2"><?= count($tasks_done) ?></span>
                </button>
            </li>
        </ul>

        <!-- CONTENU TABLEAU -->
        <div class="tab-content" id="myTabContent">
            
            <!-- PANE 1: EN COURS -->
            <div class="tab-pane fade show active" id="todo-pane">
                <div class="row g-0 border rounded shadow-sm overflow-hidden bg-body" style="height: calc(100vh - 240px);">
                    
                    <!-- COLONNE GAUCHE : LISTE -->
                    <div class="col-md-7 border-end d-flex flex-column mb-3 mb-md-0">
                        <!-- Header Tableau (AVEC FILTRES INTÉGRÉS) -->
                        <div class="p-2 border-bottom small d-flex align-items-center sticky-top text-white" style="z-index: 5; background-color: #0f4c75;">
                            <!-- Checkbox space -->
                            <!-- Checkbox space -->
                            <div style="width: 30px;"></div>
                            
                            <!-- Filtre Priorité (Hidden on Mobile) -->
                            <div class="flex-shrink-0 me-1 d-none d-md-block" style="width: 80px;">
                                <select id="filter-priorite" class="form-select form-select-sm border-0 bg-transparent fw-bold text-white p-0 shadow-none" onchange="applyFilters()">
                                    <option value="" class="text-dark">Priorité</option>
                                    <option value="URGENT" class="text-dark">Urgent</option>
                                    <option value="Normale" class="text-dark">Normale</option>
                                    <option value="Basse" class="text-dark">Basse</option>
                                </select>
                            </div>
                            
                            <!-- Filtre Chantier (Select2) - Full Width on Mobile via CSS Overrides or Flexible -->
                            <!-- On Desktop: 250px. On Mobile: Flex Grow? No, Select2 needs width. Let's hide filter on mobile header if it's too cramped, or make it smaller. -->
                            <!-- User wants to see "on ne voit rien". Let's keep it but adjust width class. -->
                            <div class="flex-shrink-0 me-2 col-7 col-md-auto" style="min-width: 150px; max-width: 250px;">
                                <select id="filter-chantier" class="form-select form-select-sm border-0" data-placeholder="Chantier / Commande" onchange="applyFilters()">
                                    <option value=""></option>
                                    <option value="NONE">- Aucun -</option>
                                    <optgroup label="Chantiers">
                                        <?php foreach($affaires_list as $a): ?>
                                            <option value="<?= htmlspecialchars($a['nom_affaire']) ?>"><?= htmlspecialchars($a['nom_affaire']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="Commandes">
                                        <?php foreach($commandes_list as $c): ?>
                                            <option value="<?= htmlspecialchars($c['ref_interne']) ?>"><?= htmlspecialchars($c['ref_interne']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                            
                            <!-- Titre (Hidden Label on Mobile) -->
                            <div class="flex-shrink-0 fw-bold text-white ps-2 d-none d-md-block" style="width: 200px;">
                                Titre
                            </div>

                            <!-- Designation (Hidden on Mobile) -->
                            <div class="flex-grow-1 fw-bold text-white ps-2 d-none d-md-block">
                                Désignation
                            </div>

                            <!-- Date (Hidden on Mobile) -->
                            <div style="width: 90px;" class="fw-bold text-white text-center d-none d-md-block">
                                Date
                            </div>
                            
                            <div style="width: 50px;" class="text-center text-white d-none d-md-block">Pts</div>
                            <div style="width: 60px;" class="text-end text-white d-none d-md-block">Act.</div>
                        </div>

                        <!-- Liste Scrollable -->
                        <div class="list-group list-group-flush flex-grow-1 overflow-auto" style="max-height: 70vh;">
                            <?php if(empty($tasks_todo)): ?>
                                <div class="p-5 text-center text-muted">
                                    <i class="fas fa-clipboard-check fa-3x mb-3 text-light"></i><br>
                                    Aucune tâche en cours.
                                </div>
                            <?php else: ?>
                                <?php foreach($tasks_todo as $t): ?>
                                    <div class="list-group-item list-group-item-action p-3 d-flex align-items-center task-row" 
                                         onclick="showDetail(this, <?= $t['id'] ?>)"
                                         data-task-id="<?= $t['id'] ?>"
                                         style="cursor: pointer; transition: all 0.2s;">
                                        <!-- Checkbox Finish -->
                                        <div style="width: 30px;" onclick="event.stopPropagation()">
                                            <a href="tasks.php?toggle=<?= $t['id'] ?>" class="text-secondary hover-success"><i class="far fa-square fa-lg"></i></a>
                                        </div>
                                        
                                        <!-- MOBILE VIEW (Stacked) -->
                                        <div class="d-md-none flex-grow-1 ps-2 overflow-hidden">
                                            <div class="d-flex align-items-center mb-1">
                                                <div class="fw-bold text-dark text-truncate flex-grow-1" style="font-size: 1rem;">
                                                    <?= htmlspecialchars($t['title']) ?>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center small text-muted">
                                                <!-- Priority Badge (Small) -->
                                                <?php if($t['importance']=='high'): ?>
                                                    <span class="badge bg-danger rounded-pill me-2" style="font-size: 0.6rem;">URGENT</span>
                                                <?php elseif($t['importance']=='low'): ?>
                                                    <span class="badge bg-info text-dark rounded-pill me-2" style="font-size: 0.6rem;">BASSE</span>
                                                <?php endif; ?>

                                                <!-- Chantier Icon + Name -->
                                                <div class="text-truncate flex-grow-1" style="max-width: 150px;">
                                                    <?php 
                                                    if($t['nom_affaire']) echo '<i class="fas fa-briefcase me-1 text-primary"></i>' . htmlspecialchars($t['nom_affaire']); 
                                                    elseif($t['ref_interne']) echo '<i class="fas fa-shopping-cart me-1 text-success"></i>' . htmlspecialchars($t['ref_interne']);
                                                    else echo '-';
                                                    ?>
                                                </div>


                                            </div>
                                        </div>
                                        


                                        <!-- DESKTOP VIEW (Columns) -->
                                        <!-- BAdge -->
                                        <div style="width: 80px;" class="me-1 d-none d-md-block">
                                            <?php if($t['importance']=='high'): ?>
                                                <span class="badge badge-glass bg-danger rounded-pill w-100">URGENT</span>
                                            <?php elseif($t['importance']=='low'): ?>
                                                <span class="badge badge-glass bg-info text-dark rounded-pill w-100">Basse</span>
                                            <?php else: ?>
                                                <span class="badge badge-glass bg-light text-dark border rounded-pill w-100">Normale</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Chantier -->
                                        <div style="width: 250px;" class="small text-truncate text-muted pe-2 me-2 d-none d-md-block">
                                            <?php 
                                            if($t['nom_affaire']) echo '<a href="affaires_detail.php?id='.$t['affaire_id'].'" class="text-decoration-none fw-bold text-primary" onclick="event.stopPropagation()"><i class="fas fa-briefcase me-1"></i>' . htmlspecialchars($t['nom_affaire']) . '</a>'; 
                                            elseif($t['ref_interne']) echo '<a href="commandes_detail.php?id='.$t['commande_id'].'" class="text-decoration-none fw-bold text-success" onclick="event.stopPropagation()"><i class="fas fa-shopping-cart me-1"></i>' . htmlspecialchars($t['ref_interne']) . '</a>';
                                            else echo '-';
                                            ?>
                                        </div>
                                        
                                        <!-- Titre -->
                                        <div style="width: 200px;" class="fw-bold text-dark text-truncate me-2 d-none d-md-block">
                                            <?= htmlspecialchars($t['title']) ?>
                                        </div>

                                        <!-- Designation (Description) -->
                                        <div class="flex-grow-1 text-muted small text-truncate pe-2 d-none d-md-block">
                                            <?= h($t['description']) ?>
                                        </div>

                                        <!-- Date -->
                                        <div style="width: 90px;" class="text-muted small text-center d-none d-md-block">
                                            <?= date('d/m/y', strtotime($t['created_at'])) ?>
                                        </div>

                                        <!-- Subtasks count -->
                                        <div style="width: 50px;" class="text-center text-muted small d-none d-md-block">
                                            <span class="badge badge-glass bg-light text-dark border" id="badge-count-<?= $t['id'] ?>"><i class="fas fa-list-ul me-1"></i><?= isset($subtasks_map[$t['id']]) ? count($subtasks_map[$t['id']]) : 0 ?></span>
                                        </div>
                                        
                                        <!-- Actions (Always Visible but compact on Mobile) -->
                                        <div class="text-end d-flex align-items-center justify-content-end" style="width: auto; min-width: 60px;">
                                            <!-- Badge Mobile -->
                                            <span class="badge badge-glass bg-light text-dark border d-md-none me-2" style="font-size: 0.6rem;"><i class="fas fa-list-ul me-1"></i><?= isset($subtasks_map[$t['id']]) ? count($subtasks_map[$t['id']]) : 0 ?></span>
                                            
                                            <a href="#" class="text-primary me-2" onclick="editTask(event, <?= $t['id'] ?>)"><i class="fas fa-edit"></i></a>
                                            <a href="tasks.php?del=<?= $t['id'] ?>" class="text-danger" onclick="return confirm('Confirmer ?'); event.stopPropagation();"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- COLONNE DROITE : DÉTAIL -->
                    <div class="col-md-5 bg-body-tertiary d-flex flex-column">
                        <div id="detail-panel" class="p-4 flex-grow-1 overflow-auto">
                            <div class="text-center text-muted h-100 d-flex flex-column justify-content-center align-items-center opacity-50">
                                <div class="mb-4 p-4 rounded-circle bg-light shadow-sm d-inline-flex">
                                    <i class="fas fa-tasks fa-3x text-primary opacity-75"></i>
                                </div>
                                <h5 class="fw-bold text-dark">Détails de la tâche</h5>
                                <p class="small text-muted">Sélectionnez une tâche pour voir le contexte</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PANE 2: TERMINÉES -->
            <div class="tab-pane fade" id="done-pane">
                <div class="row g-0 border rounded shadow-sm overflow-hidden bg-body" style="height: calc(100vh - 240px);">
                    
                    <!-- COLONNE GAUCHE : LISTE -->
                    <div class="col-md-7 border-end d-flex flex-column">
                        <!-- Header Tableau -->
                        <div class="p-2 border-bottom fw-bold small d-flex align-items-center text-white" style="background-color: #0f4c75;">
                            <div style="width: 30px;"></div>
                            <!-- Filtre Priorité -->
                            <div class="flex-shrink-0 me-1 d-none d-md-block" style="width: 80px;">
                                <select id="filter-priorite-done" class="form-select form-select-sm border-0 bg-transparent fw-bold text-white p-0 shadow-none" onchange="applyDoneFilters()">
                                    <option value="" class="text-dark">Priorité</option>
                                    <option value="URGENT" class="text-dark">Urgent</option>
                                    <option value="Normale" class="text-dark">Normale</option>
                                    <option value="Basse" class="text-dark">Basse</option>
                                </select>
                            </div>
                            
                            <!-- Filtre Chantier (Select2) -->
                            <div class="flex-shrink-0 me-2 col-7 col-md-auto" style="min-width: 150px; max-width: 250px;">
                                <select id="filter-chantier-done" class="form-select form-select-sm border-0" data-placeholder="Chantier / Commande" onchange="applyDoneFilters()">
                                    <option value=""></option>
                                    <option value="NONE">- Aucun -</option>
                                    <optgroup label="Chantiers">
                                        <?php foreach($affaires_list as $a): ?>
                                            <option value="<?= htmlspecialchars($a['nom_affaire']) ?>"><?= htmlspecialchars($a['nom_affaire']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="Commandes">
                                        <?php foreach($commandes_list as $c): ?>
                                            <option value="<?= htmlspecialchars($c['ref_interne']) ?>"><?= htmlspecialchars($c['ref_interne']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                            <div style="width: 200px;" class="me-2 d-none d-md-block">Titre</div>
                            <div class="flex-grow-1 d-none d-md-block">Désignation</div>
                            <div style="width: 90px;" class="text-center d-none d-md-block">Date</div>
                            <div style="width: 50px;" class="text-center d-none d-md-block">Pts</div>
                            <div style="width: 60px;" class="text-end d-none d-md-block">Act.</div>
                        </div>

                        <!-- Liste Scrollable -->
                        <div class="list-group list-group-flush flex-grow-1 overflow-auto" style="max-height: 70vh;">
                            <?php if(empty($tasks_done)): ?>
                                <div class="p-5 text-center text-muted">
                                    <i class="fas fa-check-circle fa-3x mb-3 text-light"></i><br>
                                    Aucune tâche terminée.
                                </div>
                            <?php else: ?>
                                <?php foreach($tasks_done as $t): ?>
                                    <div class="list-group-item list-group-item-action p-3 d-flex align-items-center task-row bg-light" 
                                         onclick="showDetail(this, <?= $t['id'] ?>)"
                                         data-task-id="<?= $t['id'] ?>"
                                         style="cursor: pointer; transition: all 0.2s;">
                                        
                                        <!-- Checkbox (Restore) -->
                                        <div style="width: 30px;" onclick="event.stopPropagation()">
                                            <a href="tasks.php?toggle=<?= $t['id'] ?>" class="text-success hover-secondary"><i class="fas fa-check-square fa-lg"></i></a>
                                        </div>

                                        <!-- MOBILE VIEW (Stacked) -->
                                        <div class="d-md-none flex-grow-1 ps-2 overflow-hidden opacity-50">
                                            <div class="d-flex align-items-center mb-1">
                                                <div class="text-muted text-decoration-line-through text-truncate flex-grow-1" style="font-size: 1rem;">
                                                    <?= htmlspecialchars($t['title']) ?>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center small text-muted">
                                                <!-- Chantier Icon + Name -->
                                                <div class="text-truncate flex-grow-1" style="max-width: 150px;">
                                                    <?php 
                                                    if($t['nom_affaire']) echo htmlspecialchars($t['nom_affaire']); 
                                                    elseif($t['ref_interne']) echo htmlspecialchars($t['ref_interne']);
                                                    else echo '-';
                                                    ?>
                                                </div>


                                            </div>
                                        </div>

                                        <!-- Priorité -->
                                        <div style="width: 80px;" class="me-1 opacity-50 d-none d-md-block">
                                            <?php if($t['importance']=='high'): ?>
                                                <span class="badge badge-glass bg-danger rounded-pill w-100">URGENT</span>
                                            <?php elseif($t['importance']=='low'): ?>
                                                <span class="badge badge-glass bg-info text-dark rounded-pill w-100">Basse</span>
                                            <?php else: ?>
                                                <span class="badge badge-glass bg-light text-dark border rounded-pill w-100">Normale</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Chantier -->
                                        <div style="width: 250px;" class="small text-truncate text-muted pe-2 me-2 opacity-50 d-none d-md-block">
                                            <?php 
                                            if($t['nom_affaire']) echo '<a href="affaires_detail.php?id='.$t['affaire_id'].'" class="text-decoration-none text-muted" onclick="event.stopPropagation()">' . htmlspecialchars($t['nom_affaire']) . '</a>'; 
                                            elseif($t['ref_interne']) echo '<a href="commandes_detail.php?id='.$t['commande_id'].'" class="text-decoration-none text-muted" onclick="event.stopPropagation()">' . htmlspecialchars($t['ref_interne']) . '</a>';
                                            else echo '-';
                                            ?>
                                        </div>

                                        <!-- Titre (Barré) -->
                                        <div style="width: 200px;" class="text-muted text-decoration-line-through text-truncate me-2 d-none d-md-block">
                                            <?= htmlspecialchars($t['title']) ?>
                                        </div>

                                        <!-- Designation -->
                                        <div class="flex-grow-1 text-muted small text-decoration-line-through text-truncate pe-2 d-none d-md-block">
                                            <?= h($t['description']) ?>
                                        </div>

                                        <!-- Date -->
                                        <div style="width: 90px;" class="text-muted small text-center opacity-50 d-none d-md-block">
                                            <?= date('d/m/y', strtotime($t['created_at'])) ?>
                                        </div>

                                        <!-- Points -->
                                        <div style="width: 50px;" class="text-center text-muted small opacity-50 d-none d-md-block">
                                            <span class="badge badge-glass bg-light text-dark border" style="font-size: 0.8rem;"><i class="fas fa-list-ul me-1"></i><?= isset($subtasks_map[$t['id']]) ? count($subtasks_map[$t['id']]) : 0 ?></span>
                                        </div>

                                        <!-- Actions -->
                                        <!-- Actions -->
                                        <div class="text-end d-flex align-items-center justify-content-end" style="width: auto; min-width: 60px;">
                                            <!-- Badge Mobile -->
                                            <span class="badge badge-glass bg-light text-dark border d-md-none me-2" style="font-size: 0.6rem;"><i class="fas fa-list-ul me-1"></i><?= isset($subtasks_map[$t['id']]) ? count($subtasks_map[$t['id']]) : 0 ?></span>
                                            
                                            <a href="tasks.php?del=<?= $t['id'] ?>" class="text-danger" onclick="return confirm('Supprimer DÉFINITIVEMENT ?'); event.stopPropagation();"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- COLONNE DROITE : DÉTAIL (PARTAGÉ) -->
                    <div class="col-md-5 bg-body-tertiary d-flex flex-column">
                        <div id="detail-panel-done" class="p-4 flex-grow-1 overflow-auto detail-container">
                            <!-- NOTE: On utilisera la même fonction showDetail, mais il faut cibler le bon conteneur. 
                                 Pour simplifier, on utilisera un SEUL ID de conteneur global si possible, ou on adaptera le JS.
                                 ACTUELLEMENT le JS cible 'detail-panel'. Ici j'ai mis 'detail-panel-done'.
                                 Je vais modifier le JS pour qu'il cible dynamiquement ou unifier les ID.
                                 
                                 Correction rapide : J'utilise le MÊME ID 'detail-panel' mais WARNING : les IDs doivent être uniques.
                                 Comme c'est des onglets, seul un est visible. Mais c'est crade.
                                 Mieux : J'utilise une classe ou je modifie le JS pour cibler le bon panel.
                            -->
                            <div class="text-center text-muted h-100 d-flex flex-column justify-content-center align-items-center opacity-50">
                                <div class="mb-4 p-4 rounded-circle bg-light shadow-sm d-inline-flex">
                                    <i class="fas fa-tasks fa-3x text-primary opacity-75"></i>
                                </div>
                                <h5 class="fw-bold text-dark">Détails de la tâche</h5>
                                <p class="small text-muted">Sélectionnez une tâche pour voir le contexte</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>


        </div>
    </div>
</div>

<!-- MODAL (Z-INDEX 99999) -->


<!-- JS DATA INJECTION (ROBUST) -->
<!-- JS DATA INJECTION (ROBUST) -->
<script>
window.tasksStore = {};
window.CSRF_TOKEN = "<?= csrf_token() ?>";

<?php 
// 1. Build the array in PHP memory first
$data_for_js = [];
foreach($tasks as $t) {
    $t_sub = isset($subtasks_map[$t['id']]) ? $subtasks_map[$t['id']] : [];
    $data_for_js[$t['id']] = [
        'id' => $t['id'],
        'title' => $t['title'],
        'description' => $t['description'],
        'importance' => $t['importance'],
        'due_date' => $t['due_date'],
        'created_at' => $t['created_at'],
        'affaire_id' => $t['affaire_id'],
        'nom_affaire' => $t['nom_affaire'],
        'commande_id' => $t['commande_id'],
        'ref_interne' => $t['ref_interne'],
        'subtasks' => $t_sub
    ];
}
// 2. Single JSON Encode (Safe) with fallback
$json = json_encode($data_for_js, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
if ($json === false) {
    echo "console.error('PHP JSON ENCODE ERROR: " . json_last_error_msg() . "');\n";
    echo "const _serverData = {};\n";
    echo "alert('ERREUR CRITIQUE DONNEES PHP');";
} else {
    echo "const _serverData = " . $json . ";\n";
}
?>
// 3. Assign to Window Store
if(_serverData) {
    window.tasksStore = _serverData;
    console.log("Tasks Data Loaded: ", Object.keys(window.tasksStore).length);
} else {
    console.error("Tasks Data Load Failed!");
}
const tasksStore = window.tasksStore; // Local alias for compatibility
</script>

<!-- JS LOGIC (ERROR HANDLING) -->
<!-- Select2 JS (Required for Filter Dropdown) -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- EXTERNAL JS LOGIC (NO CACHE) -->
<script src="assets/js/tasks.js?v=<?= time() ?>"></script>

<!-- MODAL DEPLACÉ ICI (POUR EVITER PB Z-INDEX) -->
<div class="modal fade modal-secure" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-backdrop-secure">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Nouvelle Tâche</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="tasks.php" method="POST">
                <div class="modal-body bg-white">
                    <input type="hidden" name="action" value="add_task">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Titre</label>
                        <input type="text" name="title" class="form-control" required placeholder="Ex: Vérifier commande client...">
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
                            <input type="date" name="task_date" class="form-control">
                        </div>
                    </div>



                    <div class="mb-3">
                        <label class="form-label fw-bold mb-2">Contexte Liaison</label>
                        <div class="d-flex gap-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="context_type" id="ctx_none" value="none" checked onchange="toggleContextFields()">
                                <label class="form-check-label" for="ctx_none">Aucun</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="context_type" id="ctx_affaire" value="affaire" onchange="toggleContextFields()">
                                <label class="form-check-label" for="ctx_affaire">Chantier</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="context_type" id="ctx_commande" value="commande" onchange="toggleContextFields()">
                                <label class="form-check-label" for="ctx_commande">Commande</label>
                            </div>
                        </div>

                        <!-- Select Affaire -->
                        <div id="wrapper_affaire" style="display:none;">
                            <select name="affaire_id" id="select_affaire" class="form-select" style="width:100%;">
                                <option value="">-- Sélectionner Chantier --</option>
                                <?php foreach($affaires_list as $a): ?>
                                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nom_affaire']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Select Commande -->
                        <div id="wrapper_commande" style="display:none;">
                            <select name="commande_id" id="select_commande" class="form-select" style="width:100%;">
                                <option value="">-- Sélectionner Commande --</option>
                                <?php foreach($commandes_list as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['ref_interne']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
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




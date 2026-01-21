<?php
// tasks.php - Gestionnaire de Tâches (Interne v2)
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$page_title = 'To Do List';
$message = "";

// Enable Error Reporting for Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- DB UPDATE : Moved to dedicated script if needed ---


// --- ACTIONS ---

// 1. AJOUTER TÂCHE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_task') {


    $title = trim($_POST['title']);
    $description = trim($_POST['description'] ?? '');
    $importance = $_POST['importance'] ?? 'normal';
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null; // NEW DATE FIELD
    $affaire_id = !empty($_POST['affaire_id']) ? $_POST['affaire_id'] : null;
    $commande_id = !empty($_POST['commande_id']) ? $_POST['commande_id'] : null;
    $redirect = !empty($_POST['redirect']) ? $_POST['redirect'] : 'tasks.php';
    
    // Checklist Items array
    $checklist = $_POST['checklist'] ?? [];

    if (!empty($title)) {
        try {
            $pdo->beginTransaction();

            // Insert Task with DUE_DATE
            $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, importance, status, affaire_id, commande_id, created_at, due_date) VALUES (?, ?, ?, ?, 'todo', ?, ?, NOW(), ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $description, $importance, $affaire_id, $commande_id, $due_date]);
            $task_id = $pdo->lastInsertId();

            // Insert Checklist Items
            if (!empty($checklist)) {
                $stmtItem = $pdo->prepare("INSERT INTO task_items (task_id, content) VALUES (?, ?)");
                foreach ($checklist as $item) {
                    if (!empty(trim($item))) {
                        $stmtItem->execute([$task_id, trim($item)]);
                    }
                }
            }

            $pdo->commit();
            
            // DEBUG: Stop before redirect
            // die("DEBUG: Insert Successful for Task ID $task_id. Redirecting to $redirect");
            
            header("Location: $redirect");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            // VISIBLE ERROR for User
            die("<h1 style='color:red; background:white; padding:20px;'>ERREUR CRITIQUE: " . $e->getMessage() . "</h1><pre>" . $e->getTraceAsString() . "</pre>");
        }
    } else {
         die("<h1 style='color:orange; background:white; padding:20px;'>ERREUR: Le titre est vide !</h1>");
    }
}
// 1b. EDITER TÂCHE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit_task') {
    $id = intval($_POST['task_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description'] ?? '');
    $importance = $_POST['importance'] ?? 'normal';
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $affaire_id = !empty($_POST['affaire_id']) ? $_POST['affaire_id'] : null;
    $commande_id = !empty($_POST['commande_id']) ? $_POST['commande_id'] : null;

    if (!empty($title)) {
        $sql = "UPDATE tasks SET title=?, description=?, importance=?, due_date=?, affaire_id=?, commande_id=? WHERE id=? AND user_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $description, $importance, $due_date, $affaire_id, $commande_id, $id, $_SESSION['user_id']]);
        
        // Note: For now, we do not edit checklist items in Edit Mode (Too complex for V1).
        // User can add new ones via the "Add Task" flow or we can add a specific "Edit Checklist" page later.
        
        header("Location: tasks.php?success=edited");
        exit;
    }
}

// 2. CHANGER STATUT (Toggle)
if (isset($_GET['toggle_id'])) {
    $id = intval($_GET['toggle_id']);
    $redirect = !empty($_GET['redirect']) ? $_GET['redirect'] : 'tasks.php';
    $stmt = $pdo->prepare("UPDATE tasks SET status = IF(status='todo','done','todo') WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    header("Location: $redirect");
    exit;
}

// 3. SUPPRIMER
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $redirect = !empty($_GET['redirect']) ? $_GET['redirect'] : 'tasks.php';
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    header("Location: $redirect");
    exit;
}

// 4. SUPPRIMER SOUS-TÂCHE
if (isset($_GET['delete_subtask_id'])) {
    $subtask_id = intval($_GET['delete_subtask_id']);
    // Verify ownership through task_id
    $stmt = $pdo->prepare("DELETE ti FROM task_items ti 
                           INNER JOIN tasks t ON ti.task_id = t.id 
                           WHERE ti.id = ? AND t.user_id = ?");
    $stmt->execute([$subtask_id, $_SESSION['user_id']]);
    header("Location: tasks.php");
    exit;
}

// 5. TOGGLE SOUS-TÂCHE (cocher/décocher)
if (isset($_GET['toggle_subtask_id'])) {
    $subtask_id = intval($_GET['toggle_subtask_id']);
    $selected_task = isset($_GET['selected_task']) ? intval($_GET['selected_task']) : null;
    
    // Verify ownership and toggle
    $stmt = $pdo->prepare("UPDATE task_items ti 
                           INNER JOIN tasks t ON ti.task_id = t.id 
                           SET ti.is_completed = NOT ti.is_completed 
                           WHERE ti.id = ? AND t.user_id = ?");
    $stmt->execute([$subtask_id, $_SESSION['user_id']]);
    
    // Redirect back with selected task to keep panel open
    $redirect = $selected_task ? "tasks.php?selected_task=$selected_task" : "tasks.php";
    header("Location: $redirect");
    exit;
}

// --- CHARGEMENT DONNÉES ---

// Listes pour Dropdowns (Limitées aux 50 plus récentes pour perf)
$affaires_list = $pdo->query("SELECT id, nom_affaire FROM affaires ORDER BY id DESC LIMIT 50")->fetchAll();
$commandes_list = $pdo->query("SELECT ca.id, ca.ref_interne, f.nom as fournisseur_nom FROM commandes_achats ca LEFT JOIN fournisseurs f ON ca.fournisseur_id = f.id ORDER BY ca.date_commande DESC LIMIT 50")->fetchAll();

// Récupérer les tâches avec jointures
$sql = "SELECT t.*, a.nom_affaire, ca.ref_interne 
        FROM tasks t 
        LEFT JOIN affaires a ON t.affaire_id = a.id 
        LEFT JOIN commandes_achats ca ON t.commande_id = ca.id 
        WHERE t.user_id = ? 
        ORDER BY t.status ASC, t.importance DESC, t.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$tasks = $stmt->fetchAll();

// Charger les sous-tâches (task_items) pour toutes les tâches
$task_ids = array_column($tasks, 'id');
$subtasks_by_task = [];
if (!empty($task_ids)) {
    $placeholders = str_repeat('?,', count($task_ids) - 1) . '?';
    $sql_items = "SELECT * FROM task_items WHERE task_id IN ($placeholders) ORDER BY id ASC";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute($task_ids);
    $all_items = $stmt_items->fetchAll();
    
    foreach ($all_items as $item) {
        $subtasks_by_task[$item['task_id']][] = $item;
    }
}

// Attacher les sous-tâches à chaque tâche
foreach ($tasks as &$task) {
    $task['subtasks'] = $subtasks_by_task[$task['id']] ?? [];
}
unset($task);

$tasks_todo = [];
$tasks_done = [];

foreach($tasks as $t) {
    if ($t['status'] === 'done') {
        $tasks_done[] = $t;
    } else {
        $tasks_todo[] = $t;
    }
}

require_once 'header.php';
?>

<style>
    /* Override the 135px padding-top from ag-main-content */
    .ag-main-content { padding-top: 1.5rem !important; }
    .main-content { margin-top: 0 !important; padding-top: 0 !important; }
/* Simple scrollable task list - page can scroll normally */
.list-group {
    max-height: 600px;
    overflow-y: auto;
}

</style>

<div class="main-content container-fluid px-4" style="padding-top: 0 !important;">
    <div class="d-flex justify-content-end align-items-center" style="margin: 0; padding: 0.5rem 0; margin-bottom: 0;">
        <button class="btn btn-light text-primary fw-bold shadow-sm py-1" onclick="openAddTaskModal()">
            <i class="fas fa-plus me-2"></i>Nouvelle Tâche
        </button>
    </div>



    <!-- TABS NAVIGATION -->
    <ul class="nav nav-tabs mb-0 border-bottom-0" id="taskTabs" role="tablist" style="margin-top: 0.3rem;">
        <li class="nav-item me-1" role="presentation">
            <button class="nav-link active fw-bold px-4 border-bottom-0" id="todo-tab" data-bs-toggle="tab" data-bs-target="#todo" type="button" role="tab" aria-controls="todo" aria-selected="true" style="color: #0d6efd !important; padding-top: 0.3rem !important; padding-bottom: 0.3rem !important;">
                <i class="fas fa-list-ul me-2"></i>En cours <span class="badge bg-primary-subtle text-primary ms-2 rounded-pill"><?= count($tasks_todo) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4 border-bottom-0" id="done-tab" data-bs-toggle="tab" data-bs-target="#done" type="button" role="tab" aria-controls="done" aria-selected="false" style="color: #212529 !important; padding-top: 0.3rem !important; padding-bottom: 0.3rem !important;">
                <i class="fas fa-check-circle me-2"></i>Terminées <span class="badge bg-secondary-subtle text-secondary ms-2 rounded-pill"><?= count($tasks_done) ?></span>
            </button>
        </li>
    </ul>

    <!-- TABS CONTENT -->
    <div class="card shadow-sm border-0 border-top-0 rounded-top-0">
        <div class="card-body p-0">
            <div class="tab-content" id="taskTabsContent">
                
                <!-- TAB 1: EN COURS -->
                <div class="tab-pane fade show active" id="todo" role="tabpanel" aria-labelledby="todo-tab">
                    <?php if (empty($tasks_todo)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-clipboard-check fa-4x text-light mb-3"></i>
                            <p class="text-muted fw-medium">Rien à faire ! Profitez-en pour prendre un café. ☕</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-0">
                            <!-- LEFT PANEL: Task List -->
                            <div class="col-md-7 border-end">
                                <!-- HEADER ROW WITH FILTERS -->
                                <div class="bg-light border-bottom p-2 sticky-top" style="top: 0; z-index: 10;">
                                    <div class="d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                                        <!-- Checkbox column -->
                                        <div class="flex-shrink-0" style="width: 24px;"></div>
                                        
                                        <!-- Priority column with filter -->
                                        <div class="flex-shrink-0" style="width: 60px;">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-link text-dark p-0 dropdown-toggle text-decoration-none fw-semibold" type="button" data-bs-toggle="dropdown" style="font-size: 0.75rem;">
                                                    Priorité
                                                </button>
                                                <div class="dropdown-menu p-2" style="min-width: 150px;">
                                                    <div class="form-check">
                                                        <input class="form-check-input filter-importance" type="checkbox" value="high" id="filter-high">
                                                        <label class="form-check-label small" for="filter-high">
                                                            <span class="badge bg-danger text-white" style="font-size: 0.65rem;">URGENT</span>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input filter-importance" type="checkbox" value="normal" id="filter-normal">
                                                        <label class="form-check-label small" for="filter-normal">Normale</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input filter-importance" type="checkbox" value="low" id="filter-low">
                                                        <label class="form-check-label small" for="filter-low">Basse</label>
                                                    </div>
                                                    <hr class="my-1">
                                                    <button class="btn btn-sm btn-link text-secondary p-0" onclick="resetFilters()">Réinitialiser</button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Chantier column with filter -->
                                        <div class="flex-shrink-0" style="width: 140px;">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-link text-dark p-0 dropdown-toggle text-decoration-none fw-semibold" type="button" data-bs-toggle="dropdown" style="font-size: 0.75rem;">
                                                    Chantier
                                                </button>
                                                <div class="dropdown-menu p-2" style="min-width: 250px; max-height: 300px; overflow-y: auto;">
                                                    <input type="text" class="form-control form-control-sm mb-2" id="search-chantier" placeholder="Rechercher...">
                                                    <div id="chantier-list">
                                                        <?php 
                                                        $chantiers = [];
                                                        foreach ($tasks_todo as $t) {
                                                            if (!empty($t['nom_affaire'])) {
                                                                $chantiers[$t['nom_affaire']] = $t['nom_affaire'];
                                                            }
                                                            if (!empty($t['ref_interne'])) {
                                                                $chantiers[$t['ref_interne']] = $t['ref_interne'];
                                                            }
                                                        }
                                                        foreach ($chantiers as $chantier): ?>
                                                            <div class="form-check chantier-option">
                                                                <input class="form-check-input filter-chantier" type="checkbox" value="<?= htmlspecialchars($chantier) ?>" id="filter-<?= md5($chantier) ?>">
                                                                <label class="form-check-label small" for="filter-<?= md5($chantier) ?>">
                                                                    <?= htmlspecialchars($chantier) ?>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <hr class="my-1">
                                                    <button class="btn btn-sm btn-link text-secondary p-0" onclick="resetFilters()">Réinitialiser</button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Title column -->
                                        <div class="flex-shrink-0 fw-semibold" style="width: 150px; font-size: 0.75rem;">
                                            Titre
                                        </div>
                                        
                                        <!-- Description column -->
                                        <div class="flex-grow-1 fw-semibold" style="font-size: 0.75rem;">
                                            Description
                                        </div>
                                        
                                        <!-- Subtasks column -->
                                        <div class="flex-shrink-0 fw-semibold text-center" style="width: 60px; font-size: 0.75rem;">
                                            Points
                                        </div>
                                        
                                        <!-- Actions column -->
                                        <div class="flex-shrink-0 fw-semibold text-center" style="width: 60px; font-size: 0.75rem;">
                                            Actions
                                        </div>
                                    </div>
                                </div>
                                
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($tasks_todo as $t): ?>
                                        <li class="list-group-item p-2 hover-bg-light transition-base task-item" 
                                            data-task-id="<?= $t['id'] ?>" 
                                            data-subtasks='<?= json_encode($t['subtasks']) ?>'
                                            data-task-data='<?= json_encode(['importance' => $t['importance'], 'nom_affaire' => $t['nom_affaire'], 'ref_interne' => $t['ref_interne']]) ?>'
                                            style="cursor: pointer;">
                                            <div class="d-flex align-items-center gap-2">
                                                <!-- Checkbox -->
                                                <a href="tasks.php?toggle_id=<?= $t['id'] ?>" class="text-decoration-none flex-shrink-0" onclick="event.stopPropagation();">
                                                    <div class="rounded-circle border border-3 border-secondary d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <i class="fas fa-check text-white" style="font-size: 0.7rem; opacity: 0;"></i>
                                                    </div>
                                                </a>
                                                
                                                <!-- Priority -->
                                                <div class="flex-shrink-0" style="width: 60px;">
                                                    <?php if($t['importance'] == 'high'): ?>
                                                        <span class="badge bg-danger text-white" style="font-size: 0.7rem;">URGENT</span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Client/Affaire -->
                                                <div class="flex-shrink-0" style="width: 140px;">
                                                    <?php if($t['nom_affaire']): ?>
                                                        <a href="affaires_detail.php?id=<?= $t['affaire_id'] ?>" class="text-decoration-none text-info fw-medium" style="font-size: 0.85rem;" onclick="event.stopPropagation();">
                                                            <i class="fas fa-folder-open me-1"></i><?= htmlspecialchars($t['nom_affaire']) ?>
                                                        </a>
                                                    <?php elseif($t['ref_interne']): ?>
                                                        <a href="commandes_detail.php?id=<?= $t['commande_id'] ?>" class="text-decoration-none text-primary fw-medium" style="font-size: 0.85rem;" onclick="event.stopPropagation();">
                                                            <i class="fas fa-shopping-cart me-1"></i><?= htmlspecialchars($t['ref_interne']) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Title -->
                                                <div class="flex-shrink-0 fw-bold text-dark" style="width: 150px; font-size: 0.9rem;">
                                                    <?= htmlspecialchars($t['title']) ?>
                                                </div>
                                                
                                                <!-- Description -->
                                                <div class="flex-grow-1 text-secondary" style="font-size: 0.85rem;">
                                                    <?= htmlspecialchars($t['description'] ?? '') ?>
                                                </div>
                                                
                                                <!-- Subtask Count -->
                                                <?php if (!empty($t['subtasks'])): ?>
                                                <div class="flex-shrink-0">
                                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.75rem;">
                                                        <i class="fas fa-list-ul me-1"></i><?= count($t['subtasks']) ?>
                                                    </span>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <!-- Actions -->
                                                <div class="actions flex-shrink-0 d-flex align-items-center gap-2">
                                                    <button class="btn btn-link text-muted p-0" onclick='event.stopPropagation(); editTask(<?= json_encode($t) ?>)' title="Modifier" style="font-size: 0.9rem;">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </button>
                                                    <a href="tasks.php?delete_id=<?= $t['id'] ?>" class="btn btn-link text-danger p-0" onclick="event.stopPropagation(); return confirm('Confirmer la suppression ?');" title="Supprimer" style="font-size: 0.9rem;">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <!-- RIGHT PANEL: Subtasks Detail -->
                            <div class="col-md-5 bg-light">
                                <div id="subtasks-panel" class="p-3">
                                    <div class="text-center text-muted py-5">
                                        <i class="fas fa-hand-pointer fa-3x mb-3"></i>
                                        <p class="fw-medium">Cliquez sur une tâche pour voir ses sous-tâches</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TAB 2: TERMINÉES -->
                <div class="tab-pane fade" id="done" role="tabpanel" aria-labelledby="done-tab">
                    <?php if (empty($tasks_done)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-inbox fa-4x text-light mb-3"></i>
                            <p class="text-muted fw-medium">Aucune tâche terminée pour le moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-0">
                            <!-- LEFT PANEL: Task List -->
                            <div class="col-md-7 border-end">
                                <!-- HEADER ROW WITH FILTERS -->
                                <div class="bg-light border-bottom p-2 sticky-top" style="top: 0; z-index: 10;">
                                    <div class="d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                                        <!-- Checkbox column -->
                                        <div class="flex-shrink-0" style="width: 24px;"></div>
                                        
                                        <!-- Priority column with filter -->
                                        <div class="flex-shrink-0" style="width: 60px;">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-link text-dark p-0 dropdown-toggle text-decoration-none fw-semibold" type="button" data-bs-toggle="dropdown" style="font-size: 0.75rem;">
                                                    Priorité
                                                </button>
                                                <div class="dropdown-menu p-2" style="min-width: 150px;">
                                                    <div class="form-check">
                                                        <input class="form-check-input filter-importance" type="checkbox" value="high" id="filter-high">
                                                        <label class="form-check-label small" for="filter-high">
                                                            <span class="badge bg-danger text-white" style="font-size: 0.65rem;">URGENT</span>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input filter-importance" type="checkbox" value="normal" id="filter-normal">
                                                        <label class="form-check-label small" for="filter-normal">Normale</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input filter-importance" type="checkbox" value="low" id="filter-low">
                                                        <label class="form-check-label small" for="filter-low">Basse</label>
                                                    </div>
                                                    <hr class="my-1">
                                                    <button class="btn btn-sm btn-link text-secondary p-0" onclick="resetFilters()">Réinitialiser</button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Chantier column with filter -->
                                        <div class="flex-shrink-0" style="width: 140px;">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-link text-dark p-0 dropdown-toggle text-decoration-none fw-semibold" type="button" data-bs-toggle="dropdown" style="font-size: 0.75rem;">
                                                    Chantier
                                                </button>
                                                <div class="dropdown-menu p-2" style="min-width: 250px; max-height: 300px; overflow-y: auto;">
                                                    <input type="text" class="form-control form-control-sm mb-2" id="search-chantier" placeholder="Rechercher...">
                                                    <div id="chantier-list">
                                                        <?php 
                                                        $chantiers = [];
                                                        foreach ($tasks_todo as $t) {
                                                            if (!empty($t['nom_affaire'])) {
                                                                $chantiers[$t['nom_affaire']] = $t['nom_affaire'];
                                                            }
                                                            if (!empty($t['ref_interne'])) {
                                                                $chantiers[$t['ref_interne']] = $t['ref_interne'];
                                                            }
                                                        }
                                                        foreach ($chantiers as $chantier): ?>
                                                            <div class="form-check chantier-option">
                                                                <input class="form-check-input filter-chantier" type="checkbox" value="<?= htmlspecialchars($chantier) ?>" id="filter-<?= md5($chantier) ?>">
                                                                <label class="form-check-label small" for="filter-<?= md5($chantier) ?>">
                                                                    <?= htmlspecialchars($chantier) ?>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <hr class="my-1">
                                                    <button class="btn btn-sm btn-link text-secondary p-0" onclick="resetFilters()">Réinitialiser</button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Title column -->
                                        <div class="flex-shrink-0 fw-semibold" style="width: 150px; font-size: 0.75rem;">
                                            Titre
                                        </div>
                                        
                                        <!-- Description column -->
                                        <div class="flex-grow-1 fw-semibold" style="font-size: 0.75rem;">
                                            Description
                                        </div>
                                        
                                        <!-- Subtasks column -->
                                        <div class="flex-shrink-0 fw-semibold text-center" style="width: 60px; font-size: 0.75rem;">
                                            Points
                                        </div>
                                        
                                        <!-- Actions column -->
                                        <div class="flex-shrink-0 fw-semibold text-center" style="width: 60px; font-size: 0.75rem;">
                                            Actions
                                        </div>
                                    </div>
                                </div>
                                
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($tasks_done as $t): ?>
                                        <li class="list-group-item p-2 hover-bg-light transition-base task-item-done" 
                                            data-task-id="<?= $t['id'] ?>" 
                                            data-subtasks='<?= json_encode($t['subtasks']) ?>'
                                            data-task-data='<?= json_encode(['importance' => $t['importance'], 'nom_affaire' => $t['nom_affaire'], 'ref_interne' => $t['ref_interne']]) ?>'
                                            style="cursor: pointer;">
                                            <div class="d-flex align-items-center gap-2">
                                                <!-- Checkbox -->
                                                <a href="tasks.php?toggle_id=<?= $t['id'] ?>" class="text-decoration-none flex-shrink-0" onclick="event.stopPropagation();">
                                                    <div class="rounded-circle bg-success border-3 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <i class="fas fa-check text-white" style="font-size: 0.7rem;"></i>
                                                    </div>
                                                </a>
                                                
                                                <!-- Priority -->
                                                <div class="flex-shrink-0" style="width: 60px;">
                                                    <?php if($t['importance'] == 'high'): ?>
                                                        <span class="badge bg-danger text-white" style="font-size: 0.7rem;">URGENT</span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Client/Affaire -->
                                                <div class="flex-shrink-0" style="width: 140px;">
                                                    <?php if($t['nom_affaire']): ?>
                                                        <a href="affaires_detail.php?id=<?= $t['affaire_id'] ?>" class="text-decoration-none text-info fw-medium" style="font-size: 0.85rem;" onclick="event.stopPropagation();">
                                                            <i class="fas fa-folder-open me-1"></i><?= htmlspecialchars($t['nom_affaire']) ?>
                                                        </a>
                                                    <?php elseif($t['ref_interne']): ?>
                                                        <a href="commandes_detail.php?id=<?= $t['commande_id'] ?>" class="text-decoration-none text-primary fw-medium" style="font-size: 0.85rem;" onclick="event.stopPropagation();">
                                                            <i class="fas fa-shopping-cart me-1"></i><?= htmlspecialchars($t['ref_interne']) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Title -->
                                                <div class="flex-shrink-0 text-decoration-line-through text-muted fw-medium" style="width: 150px; font-size: 0.9rem;">
                                                    <?= htmlspecialchars($t['title']) ?>
                                                </div>
                                                
                                                <!-- Description -->
                                                <div class="flex-grow-1 text-secondary text-decoration-line-through" style="font-size: 0.85rem;">
                                                    <?= htmlspecialchars($t['description'] ?? '') ?>
                                                </div>
                                                
                                                <!-- Subtask Count -->
                                                <?php if (!empty($t['subtasks'])): ?>
                                                <div class="flex-shrink-0">
                                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.75rem;">
                                                        <i class="fas fa-list-ul me-1"></i><?= count($t['subtasks']) ?>
                                                    </span>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <!-- Actions -->
                                                <div class="actions flex-shrink-0 d-flex align-items-center gap-2">
                                                    <a href="tasks.php?delete_id=<?= $t['id'] ?>" class="btn btn-link text-danger p-0" onclick="event.stopPropagation(); return confirm('Confirmer la suppression ?');" title="Supprimer" style="font-size: 0.9rem;">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <!-- RIGHT PANEL: Subtasks Detail -->
                            <div class="col-md-5 bg-light">
                                <div id="subtasks-panel-done" class="p-3">
                                    <div class="text-center text-muted py-5">
                                        <i class="fas fa-hand-pointer fa-3x mb-3"></i>
                                        <p class="fw-medium">Cliquez sur une tâche pour voir ses sous-tâches</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

<script>
// Handle task selection and subtask display
document.addEventListener('DOMContentLoaded', function() {
    const taskItems = document.querySelectorAll('.task-item');
    const subtasksPanel = document.getElementById('subtasks-panel');
    
    // Auto-select task if selected_task parameter exists
    const urlParams = new URLSearchParams(window.location.search);
    const selectedTaskId = urlParams.get('selected_task');
    if (selectedTaskId) {
        const taskToSelect = document.querySelector(`.task-item[data-task-id="${selectedTaskId}"]`);
        if (taskToSelect) {
            taskToSelect.click();
        }
    }
    
    // Handle completed tasks (same logic)
    const taskItemsDone = document.querySelectorAll('.task-item-done');
    const subtasksPanelDone = document.getElementById('subtasks-panel-done');
    
    taskItemsDone.forEach(item => {
        item.addEventListener('click', function() {
            // Remove active class from all tasks
            taskItemsDone.forEach(t => t.classList.remove('bg-primary-subtle'));
            // Add active class to clicked task
            this.classList.add('bg-primary-subtle');
            
            // Get subtasks data
            const subtasks = JSON.parse(this.dataset.subtasks || '[]');
            const taskId = this.dataset.taskId;
            
            // Display subtasks
            if (subtasks.length === 0) {
                subtasksPanelDone.innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p class="fw-medium">Aucune sous-tâche pour cette tâche</p>
                    </div>
                `;
            } else {
                let html = '<h6 class="text-success mb-3"><i class="fas fa-check-circle me-2"></i>Sous-tâches (Terminée)</h6>';
                html += '<ul class="list-group">';
                subtasks.forEach(sub => {
                    const checked = sub.is_completed ? 'checked' : '';
                    const textClass = sub.is_completed ? 'text-decoration-line-through text-muted' : '';
                    html += `
                        <li class="list-group-item d-flex align-items-center justify-content-between gap-2 py-2">
                            <div class="d-flex align-items-center gap-2 flex-grow-1">
                                <input type="checkbox" ${checked} class="form-check-input m-0" 
                                       style="width: 20px; height: 20px; border-width: 2px; cursor: pointer;"
                                       onclick="window.location.href='tasks.php?toggle_subtask_id=${sub.id}&selected_task=${taskId}'">
                                <span class="${textClass}" style="cursor: pointer;" 
                                      onclick="window.location.href='tasks.php?toggle_subtask_id=${sub.id}&selected_task=${taskId}'">${sub.content}</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-link text-danger p-0" onclick="deleteSubtask(${sub.id})" title="Supprimer" style="font-size: 0.85rem;">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </li>
                    `;
                });
                html += '</ul>';
                subtasksPanelDone.innerHTML = html;
            }
        });
    });
    
    taskItems.forEach(item => {
        item.addEventListener('click', function() {
            // Remove active class from all tasks
            taskItems.forEach(t => t.classList.remove('bg-primary-subtle'));
            // Add active class to clicked task
            this.classList.add('bg-primary-subtle');
            
            // Get subtasks data
            const subtasks = JSON.parse(this.dataset.subtasks || '[]');
            const taskId = this.dataset.taskId;
            
            // Display subtasks
            if (subtasks.length === 0) {
                subtasksPanel.innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p class="fw-medium">Aucune sous-tâche pour cette tâche</p>
                    </div>
                `;
            } else {
                let html = '<h6 class="text-primary mb-3"><i class="fas fa-list-ul me-2"></i>Sous-tâches</h6>';
                html += '<ul class="list-group">';
                subtasks.forEach(sub => {
                    const checked = sub.is_completed ? 'checked' : '';
                    const textClass = sub.is_completed ? 'text-decoration-line-through text-muted' : '';
                    html += `
                        <li class="list-group-item d-flex align-items-center justify-content-between gap-2 py-2">
                            <div class="d-flex align-items-center gap-2 flex-grow-1">
                                <input type="checkbox" ${checked} class="form-check-input m-0" 
                                       style="width: 20px; height: 20px; border-width: 2px; cursor: pointer;"
                                       onclick="window.location.href='tasks.php?toggle_subtask_id=${sub.id}&selected_task=${taskId}'">
                                <span class="${textClass}" style="cursor: pointer;" 
                                      onclick="window.location.href='tasks.php?toggle_subtask_id=${sub.id}&selected_task=${taskId}'">${sub.content}</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-link text-muted p-0" onclick="editSubtask(${sub.id})" title="Modifier" style="font-size: 0.85rem;">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="btn btn-link text-danger p-0" onclick="deleteSubtask(${sub.id})" title="Supprimer" style="font-size: 0.85rem;">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </li>
                    `;
                });
                html += '</ul>';
                subtasksPanel.innerHTML = html;
            }
        });
    });
});

// Placeholder functions for subtask management
function editSubtask(id) {
    alert('Édition de la sous-tâche #' + id + ' (à implémenter)');
}

function deleteSubtask(id) {
    if (confirm('Confirmer la suppression de cette sous-tâche ?')) {
        window.location.href = 'tasks.php?delete_subtask_id=' + id;
    }
}

function toggleSubtask(id) {
    window.location.href = 'tasks.php?toggle_subtask_id=' + id;
}

// Filter functions
function resetFilters() {
    document.querySelectorAll('.filter-importance, .filter-chantier').forEach(cb => cb.checked = false);
    applyFilters();
}

function resetFiltersDone() {
    document.querySelectorAll('.filter-importance-done, .filter-chantier-done').forEach(cb => cb.checked = false);
    applyFiltersDone();
}

function applyFiltersDone() {
    const selectedImportance = Array.from(document.querySelectorAll('.filter-importance-done:checked')).map(cb => cb.value);
    const selectedChantiers = Array.from(document.querySelectorAll('.filter-chantier-done:checked')).map(cb => cb.value);
    const taskItems = document.querySelectorAll('.task-item-done');
    
    taskItems.forEach(item => {
        let show = true;
        const taskData = JSON.parse(item.dataset.taskData || '{}');
        
        if (selectedImportance.length > 0) {
            if (!selectedImportance.includes(taskData.importance)) {
                show = false;
            }
        }
        
        if (selectedChantiers.length > 0) {
            const affaireName = taskData.nom_affaire || '';
            const refInterne = taskData.ref_interne || '';
            if (!selectedChantiers.includes(affaireName) && !selectedChantiers.includes(refInterne)) {
                show = false;
            }
        }
        
        item.style.display = show ? '' : 'none';
    });
}

function applyFilters() {
    const selectedImportance = Array.from(document.querySelectorAll('.filter-importance:checked')).map(cb => cb.value);
    const selectedChantiers = Array.from(document.querySelectorAll('.filter-chantier:checked')).map(cb => cb.value);
    const taskItems = document.querySelectorAll('.task-item');
    
    taskItems.forEach(item => {
        let show = true;
        const taskData = JSON.parse(item.dataset.taskData || '{}');
        
        // Filter by importance (if any selected)
        if (selectedImportance.length > 0) {
            if (!selectedImportance.includes(taskData.importance)) {
                show = false;
            }
        }
        
        // Filter by chantier (if any selected)
        if (selectedChantiers.length > 0) {
            const affaireName = taskData.nom_affaire || '';
            const refInterne = taskData.ref_interne || '';
            if (!selectedChantiers.includes(affaireName) && !selectedChantiers.includes(refInterne)) {
                show = false;
            }
        }
        
        item.style.display = show ? '' : 'none';
    });
}

// Attach filter listeners
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.filter-importance, .filter-chantier').forEach(cb => {
        cb.addEventListener('change', applyFilters);
    });
    
    // Search in chantier dropdown
    const searchInput = document.getElementById('search-chantier');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.chantier-option').forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
    
    // Filters for done tab
    document.querySelectorAll('.filter-importance-done, .filter-chantier-done').forEach(cb => {
        cb.addEventListener('change', applyFiltersDone);
    });
    
    const searchInputDone = document.getElementById('search-chantier-done');
    if (searchInputDone) {
        searchInputDone.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.chantier-option-done').forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});
</script>
<!-- MODAL ADD TASK -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <form method="POST" id="taskForm">
          <input type="hidden" name="action" id="formAction" value="add_task">
          <input type="hidden" name="task_id" id="taskId" value="">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title fw-bold" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Nouvelle Tâche</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label fw-bold">Titre de la tâche</label>
                    <input type="text" class="form-control form-control-lg" name="title" id="taskTitle" placeholder="Ex: Relancer client Dupont..." required autofocus>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Échéance</label>
                   <input type="date" class="form-control form-control-lg" name="due_date" id="taskDate">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Importance</label>
                <select class="form-select" name="importance" id="taskImportance">
                    <option value="normal">Normale</option>
                    <option value="high">🔴 Haute Priorité</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Description</label>
                <textarea class="form-control" name="description" id="taskDesc" rows="2" placeholder="Détails supplémentaires..."></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold mb-1">Checklist (Sous-tâches)</label>
                <div id="checklist-container">
                    <!-- Dynamic inputs -->
                </div>
                <button type="button" class="btn btn-sm btn-light text-primary mt-2 border" onclick="addChecklistItem()">
                    <i class="fas fa-plus me-1"></i> Ajouter un point
                </button>
            </div>

            <hr class="my-4">
            <h6 class="text-muted text-uppercase small fw-bold mb-3"><i class="fas fa-link me-2"></i>Lier à... (Optionnel)</h6>

                <label class="form-label text-muted small">Affaire / Client</label>
                <select id="select-affaire" class="form-select" name="affaire_id" placeholder="Rechercher une affaire...">
                    <option value="">Aucune liaison</option>
                    <?php foreach($affaires_list as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nom_affaire']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted small">Commande Fournisseur</label>
                <select id="select-commande" class="form-select" name="commande_id" placeholder="Rechercher une commande...">
                    <option value="">Aucune liaison</option>
                    <?php foreach($commandes_list as $c): ?>
                        <option value="<?= $c['id'] ?>">
                            <?= htmlspecialchars($c['ref_interne'] ?: 'CMD #'.$c['id']) ?> - <?= htmlspecialchars($c['fournisseur_nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- BUTTONS INTEGRATED IN BODY -->
            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm">
                    <i class="fas fa-check-circle me-2"></i><span id="submitBtnText">AJOUTER LA TÂCHE</span>
                </button>
                <button type="button" class="btn btn-light text-muted border-0 small" data-bs-dismiss="modal">
                    Annuler
                </button>
            </div>

          </div>
          <!-- No more modal-footer for cleaner look -->
      </form>
    </div>
  </div>
</div>

<!-- Select2 (Dropdown avec Recherche) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js">
// Placeholder functions for subtask management
function editSubtask(id) {
    alert('Édition de la sous-tâche #' + id + ' (à implémenter)');
}

function deleteSubtask(id) {
    if (confirm('Confirmer la suppression de cette sous-tâche ?')) {
        window.location.href = 'tasks.php?delete_subtask_id=' + id;
    }
}

function toggleSubtask(id) {
    window.location.href = 'tasks.php?toggle_subtask_id=' + id;
}

// Filter functions
function resetFilters() {
    document.querySelectorAll('.filter-importance, .filter-chantier').forEach(cb => cb.checked = false);
    applyFilters();
}

function resetFiltersDone() {
    document.querySelectorAll('.filter-importance-done, .filter-chantier-done').forEach(cb => cb.checked = false);
    applyFiltersDone();
}

function applyFiltersDone() {
    const selectedImportance = Array.from(document.querySelectorAll('.filter-importance-done:checked')).map(cb => cb.value);
    const selectedChantiers = Array.from(document.querySelectorAll('.filter-chantier-done:checked')).map(cb => cb.value);
    const taskItems = document.querySelectorAll('.task-item-done');
    
    taskItems.forEach(item => {
        let show = true;
        const taskData = JSON.parse(item.dataset.taskData || '{}');
        
        if (selectedImportance.length > 0) {
            if (!selectedImportance.includes(taskData.importance)) {
                show = false;
            }
        }
        
        if (selectedChantiers.length > 0) {
            const affaireName = taskData.nom_affaire || '';
            const refInterne = taskData.ref_interne || '';
            if (!selectedChantiers.includes(affaireName) && !selectedChantiers.includes(refInterne)) {
                show = false;
            }
        }
        
        item.style.display = show ? '' : 'none';
    });
}

function applyFilters() {
    const selectedImportance = Array.from(document.querySelectorAll('.filter-importance:checked')).map(cb => cb.value);
    const selectedChantiers = Array.from(document.querySelectorAll('.filter-chantier:checked')).map(cb => cb.value);
    const taskItems = document.querySelectorAll('.task-item');
    
    taskItems.forEach(item => {
        let show = true;
        const taskData = JSON.parse(item.dataset.taskData || '{}');
        
        // Filter by importance (if any selected)
        if (selectedImportance.length > 0) {
            if (!selectedImportance.includes(taskData.importance)) {
                show = false;
            }
        }
        
        // Filter by chantier (if any selected)
        if (selectedChantiers.length > 0) {
            const affaireName = taskData.nom_affaire || '';
            const refInterne = taskData.ref_interne || '';
            if (!selectedChantiers.includes(affaireName) && !selectedChantiers.includes(refInterne)) {
                show = false;
            }
        }
        
        item.style.display = show ? '' : 'none';
    });
}

// Attach filter listeners
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.filter-importance, .filter-chantier').forEach(cb => {
        cb.addEventListener('change', applyFilters);
    });
    
    // Search in chantier dropdown
    const searchInput = document.getElementById('search-chantier');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.chantier-option').forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
    
    // Filters for done tab
    document.querySelectorAll('.filter-importance-done, .filter-chantier-done').forEach(cb => {
        cb.addEventListener('change', applyFiltersDone);
    });
    
    const searchInputDone = document.getElementById('search-chantier-done');
    if (searchInputDone) {
        searchInputDone.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.chantier-option-done').forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js">
// Placeholder functions for subtask management
function editSubtask(id) {
    alert('Édition de la sous-tâche #' + id + ' (à implémenter)');
}

function deleteSubtask(id) {
    if (confirm('Confirmer la suppression de cette sous-tâche ?')) {
        window.location.href = 'tasks.php?delete_subtask_id=' + id;
    }
}

function toggleSubtask(id) {
    window.location.href = 'tasks.php?toggle_subtask_id=' + id;
}

// Filter functions
function resetFilters() {
    document.querySelectorAll('.filter-importance, .filter-chantier').forEach(cb => cb.checked = false);
    applyFilters();
}

function resetFiltersDone() {
    document.querySelectorAll('.filter-importance-done, .filter-chantier-done').forEach(cb => cb.checked = false);
    applyFiltersDone();
}

function applyFiltersDone() {
    const selectedImportance = Array.from(document.querySelectorAll('.filter-importance-done:checked')).map(cb => cb.value);
    const selectedChantiers = Array.from(document.querySelectorAll('.filter-chantier-done:checked')).map(cb => cb.value);
    const taskItems = document.querySelectorAll('.task-item-done');
    
    taskItems.forEach(item => {
        let show = true;
        const taskData = JSON.parse(item.dataset.taskData || '{}');
        
        if (selectedImportance.length > 0) {
            if (!selectedImportance.includes(taskData.importance)) {
                show = false;
            }
        }
        
        if (selectedChantiers.length > 0) {
            const affaireName = taskData.nom_affaire || '';
            const refInterne = taskData.ref_interne || '';
            if (!selectedChantiers.includes(affaireName) && !selectedChantiers.includes(refInterne)) {
                show = false;
            }
        }
        
        item.style.display = show ? '' : 'none';
    });
}

function applyFilters() {
    const selectedImportance = Array.from(document.querySelectorAll('.filter-importance:checked')).map(cb => cb.value);
    const selectedChantiers = Array.from(document.querySelectorAll('.filter-chantier:checked')).map(cb => cb.value);
    const taskItems = document.querySelectorAll('.task-item');
    
    taskItems.forEach(item => {
        let show = true;
        const taskData = JSON.parse(item.dataset.taskData || '{}');
        
        // Filter by importance (if any selected)
        if (selectedImportance.length > 0) {
            if (!selectedImportance.includes(taskData.importance)) {
                show = false;
            }
        }
        
        // Filter by chantier (if any selected)
        if (selectedChantiers.length > 0) {
            const affaireName = taskData.nom_affaire || '';
            const refInterne = taskData.ref_interne || '';
            if (!selectedChantiers.includes(affaireName) && !selectedChantiers.includes(refInterne)) {
                show = false;
            }
        }
        
        item.style.display = show ? '' : 'none';
    });
}

// Attach filter listeners
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.filter-importance, .filter-chantier').forEach(cb => {
        cb.addEventListener('change', applyFilters);
    });
    
    // Search in chantier dropdown
    const searchInput = document.getElementById('search-chantier');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.chantier-option').forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
    
    // Filters for done tab
    document.querySelectorAll('.filter-importance-done, .filter-chantier-done').forEach(cb => {
        cb.addEventListener('change', applyFiltersDone);
    });
    
    const searchInputDone = document.getElementById('search-chantier-done');
    if (searchInputDone) {
        searchInputDone.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.chantier-option-done').forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});
</script>
<script>
    function addChecklistItem() {
        const container = document.getElementById('checklist-container');
        const div = document.createElement('div');
        div.className = 'input-group mb-2';
        div.innerHTML = `
            <span class="input-group-text bg-white"><i class="far fa-square"></i></span>
            <input type="text" class="form-control form-control-sm" name="checklist[]" placeholder="Point à vérifier..." autofocus>
            <button type="button" class="btn btn-outline-danger border-start-0" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(div);
        // Focus on the new input
        div.querySelector('input').focus();
    }

    $(document).ready(function() {
        // Fix Focus in Bootstrap Modal
        $.fn.modal.Constructor.prototype.enforceFocus = function() {};

        $('#select-affaire').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Rechercher une affaire...',
            allowClear: true,
            dropdownParent: $('#addTaskModal')
        });
        $('#select-commande').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Rechercher une commande...',
            allowClear: true,
            dropdownParent: $('#addTaskModal')
        });
    });
    function openAddTaskModal() {
        // Reset Form
        document.getElementById('taskForm').reset();
        document.getElementById('formAction').value = 'add_task';
        document.getElementById('taskId').value = '';
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle me-2"></i>Nouvelle Tâche';
        document.getElementById('submitBtnText').innerText = 'AJOUTER LA TÂCHE';
        
        // Reset Select2
        $('#select-affaire').val(null).trigger('change');
        $('#select-commande').val(null).trigger('change');
        
        // Show
        new bootstrap.Modal(document.getElementById('addTaskModal')).show();
    }

    function editTask(task) {
        // Populate Form
        document.getElementById('formAction').value = 'edit_task';
        document.getElementById('taskId').value = task.id;
        document.getElementById('taskTitle').value = task.title;
        document.getElementById('taskDesc').value = task.description || '';
        document.getElementById('taskImportance').value = task.importance;
        document.getElementById('taskDate').value = task.due_date || '';
        
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-pencil-alt me-2"></i>Modifier Tâche';
        document.getElementById('submitBtnText').innerText = 'ENREGISTRER MODIFICATIONS';
        
        // Set Select2
        if (task.affaire_id) {
            $('#select-affaire').val(task.affaire_id).trigger('change');
        } else {
             $('#select-affaire').val(null).trigger('change');
        }
        
        if (task.commande_id) {
            $('#select-commande').val(task.commande_id).trigger('change');
        } else {
             $('#select-commande').val(null).trigger('change');
        }

        // Show
        new bootstrap.Modal(document.getElementById('addTaskModal')).show();
    }

// Placeholder functions for subtask management
function editSubtask(id) {
    alert('Édition de la sous-tâche #' + id + ' (à implémenter)');
}

function deleteSubtask(id) {
    if (confirm('Confirmer la suppression de cette sous-tâche ?')) {
        window.location.href = 'tasks.php?delete_subtask_id=' + id;
    }
}

function toggleSubtask(id) {
    window.location.href = 'tasks.php?toggle_subtask_id=' + id;
}

// Filter functions
function resetFilters() {
    document.querySelectorAll('.filter-importance, .filter-chantier').forEach(cb => cb.checked = false);
    applyFilters();
}

function resetFiltersDone() {
    document.querySelectorAll('.filter-importance-done, .filter-chantier-done').forEach(cb => cb.checked = false);
    applyFiltersDone();
}

function applyFiltersDone() {
    const selectedImportance = Array.from(document.querySelectorAll('.filter-importance-done:checked')).map(cb => cb.value);
    const selectedChantiers = Array.from(document.querySelectorAll('.filter-chantier-done:checked')).map(cb => cb.value);
    const taskItems = document.querySelectorAll('.task-item-done');
    
    taskItems.forEach(item => {
        let show = true;
        const taskData = JSON.parse(item.dataset.taskData || '{}');
        
        if (selectedImportance.length > 0) {
            if (!selectedImportance.includes(taskData.importance)) {
                show = false;
            }
        }
        
        if (selectedChantiers.length > 0) {
            const affaireName = taskData.nom_affaire || '';
            const refInterne = taskData.ref_interne || '';
            if (!selectedChantiers.includes(affaireName) && !selectedChantiers.includes(refInterne)) {
                show = false;
            }
        }
        
        item.style.display = show ? '' : 'none';
    });
}

function applyFilters() {
    const selectedImportance = Array.from(document.querySelectorAll('.filter-importance:checked')).map(cb => cb.value);
    const selectedChantiers = Array.from(document.querySelectorAll('.filter-chantier:checked')).map(cb => cb.value);
    const taskItems = document.querySelectorAll('.task-item');
    
    taskItems.forEach(item => {
        let show = true;
        const taskData = JSON.parse(item.dataset.taskData || '{}');
        
        // Filter by importance (if any selected)
        if (selectedImportance.length > 0) {
            if (!selectedImportance.includes(taskData.importance)) {
                show = false;
            }
        }
        
        // Filter by chantier (if any selected)
        if (selectedChantiers.length > 0) {
            const affaireName = taskData.nom_affaire || '';
            const refInterne = taskData.ref_interne || '';
            if (!selectedChantiers.includes(affaireName) && !selectedChantiers.includes(refInterne)) {
                show = false;
            }
        }
        
        item.style.display = show ? '' : 'none';
    });
}

// Attach filter listeners
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.filter-importance, .filter-chantier').forEach(cb => {
        cb.addEventListener('change', applyFilters);
    });
    
    // Search in chantier dropdown
    const searchInput = document.getElementById('search-chantier');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.chantier-option').forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
    
    // Filters for done tab
    document.querySelectorAll('.filter-importance-done, .filter-chantier-done').forEach(cb => {
        cb.addEventListener('change', applyFiltersDone);
    });
    
    const searchInputDone = document.getElementById('search-chantier-done');
    if (searchInputDone) {
        searchInputDone.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.chantier-option-done').forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});
</script>

<style>
/* Petits ajustements CSS locaux */
.hover-bg-light:hover { background-color: #f8f9fa; }
.transition-base { transition: all 0.2s ease; }
.hover-danger:hover { color: #dc3545 !important; }
.text-xs { font-size: 0.75rem; }
/* Fix Select2 in Bootstrap 5 Modal */
.select2-container {
    z-index: 99999 !important; /* Très haut pour passer au dessus de la modal */
}
.select2-dropdown {
    z-index: 99999 !important;
}
/* Simple scrollable task list - page can scroll normally */
.list-group {
    max-height: 600px;
    overflow-y: auto;
}

</style>

<!-- MODAL ADD/EDIT TASK -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Nouvelle Tâche</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="tasks.php">
                <input type="hidden" name="action" value="add_task" id="form-action">
                <input type="hidden" name="task_id" id="form-task-id">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Titre <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="form-title" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="form-description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Priorité</label>
                            <select name="importance" id="form-importance" class="form-select">
                                <option value="low">Basse</option>
                                <option value="normal" selected>Normale</option>
                                <option value="high">Urgente</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Échéance</label>
                            <input type="date" name="due_date" id="form-due-date" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Affaire</label>
                        <select name="affaire_id" id="select-affaire" class="form-select">
                            <option value="">-- Aucune --</option>
                            <?php foreach ($affaires_list as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nom_affaire']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Commande</label>
                        <select name="commande_id" id="select-commande" class="form-select">
                            <option value="">-- Aucune --</option>
                            <?php foreach ($commandes_list as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['ref_interne']) ?> - <?= htmlspecialchars($c['fournisseur_nom'] ?? 'N/A') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Open modal for adding new task
function openAddTaskModal() {
    document.getElementById('modalTitle').textContent = 'Nouvelle Tâche';
    document.getElementById('form-action').value = 'add_task';
    document.getElementById('form-task-id').value = '';
    document.getElementById('form-title').value = '';
    document.getElementById('form-description').value = '';
    document.getElementById('form-importance').value = 'normal';
    document.getElementById('form-due-date').value = '';
    document.getElementById('select-affaire').value = '';
    document.getElementById('select-commande').value = '';
    
    new bootstrap.Modal(document.getElementById('addTaskModal')).show();
}

// Open modal for editing existing task
function editTask(task) {
    document.getElementById('modalTitle').textContent = 'Modifier la Tâche';
    document.getElementById('form-action').value = 'edit_task';
    document.getElementById('form-task-id').value = task.id;
    document.getElementById('form-title').value = task.title || '';
    document.getElementById('form-description').value = task.description || '';
    document.getElementById('form-importance').value = task.importance || 'normal';
    document.getElementById('form-due-date').value = task.due_date || '';
    document.getElementById('select-affaire').value = task.affaire_id || '';
    document.getElementById('select-commande').value = task.commande_id || '';
    
    new bootstrap.Modal(document.getElementById('addTaskModal')).show();
}
</script>


</body>
</html>

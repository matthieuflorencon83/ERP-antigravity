<?php
// DEBUG: OFF
// ini_set('display_errors', 0); // En prod

require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

// --- DATA FETCHING ---
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
$tasks_todo = [];
$tasks_done = [];
foreach($tasks as $t) {
    if ($t['status'] === 'done') $tasks_done[] = $t;
    else $tasks_todo[] = $t;
}

// Récupérer les sous-tâches
$task_ids = array_column($tasks, 'id');
$subtasks_map = [];
if (!empty($task_ids)) {
    $placeholders = str_repeat('?,', count($task_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM task_items WHERE task_id IN ($placeholders)");
    $stmt->execute($task_ids);
    $all = $stmt->fetchAll();
    foreach($all as $i) $subtasks_map[$i['task_id']][] = $i;
}

// Récupérer data pour filtres
$stmt_aff = $pdo->query("SELECT id, nom_affaire FROM affaires ORDER BY nom_affaire ASC");
$affaires_list = $stmt_aff->fetchAll();
$stmt_cmd = $pdo->query("SELECT id, ref_interne FROM commandes_achats ORDER BY ref_interne ASC");
// Récupérer data pour filtres
$stmt_aff = $pdo->query("SELECT id, nom_affaire FROM affaires ORDER BY nom_affaire ASC");
$affaires_list = $stmt_aff->fetchAll();
$commandes_list = $stmt_cmd->fetchAll();

$page_title = "To Do List"; // Titre dans le bandeau
require_once 'header.php'; // RÉACTIVATION DU VRAI HEADER
?>

<!-- FIX LAYOUT : On enlève les fermetures manuelles risquées -->
<!-- On pousse le contenu vers le bas pour éviter le header fixe -->

<div id="real-content" style="position: relative; z-index: 10; background: #fff; min-height: 100vh; margin-top: -60px; padding-top: 20px; border-radius: 15px 15px 0 0;">
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
                <button class="nav-link active fw-bold text-primary border" id="todo-tab" data-bs-toggle="tab" data-bs-target="#todo-pane">
                    <i class="fas fa-list-ul me-2"></i>EN COURS <span class="badge bg-primary ms-2"><?= count($tasks_todo) ?></span>
                </button>
            </li>
            <li class="nav-item ms-2">
                <button class="nav-link fw-bold text-secondary border" id="done-tab" data-bs-toggle="tab" data-bs-target="#done-pane">
                    <i class="fas fa-check me-2"></i>TERMINÉES <span class="badge bg-secondary ms-2"><?= count($tasks_done) ?></span>
                </button>
            </li>
        </ul>

        <!-- CONTENU TABLEAU -->
        <div class="tab-content" id="myTabContent">
            
            <!-- PANE 1: EN COURS -->
            <div class="tab-pane fade show active" id="todo-pane">
                <div class="row g-0 border rounded shadow-sm overflow-hidden" style="min-height: 600px; background: #fff;">
                    
                    <!-- COLONNE GAUCHE : LISTE -->
                    <div class="col-md-7 border-end d-flex flex-column">
                        <!-- Header Tableau -->
                        <div class="bg-light p-2 border-bottom fw-bold text-muted small d-flex">
                            <div style="width: 30px;"></div>
                            <div style="width: 80px;">Priorité</div>
                            <div style="width: 150px;">Chantier</div>
                            <div class="flex-grow-1">Titre</div>
                            <div style="width: 50px;">Pts</div>
                            <div style="width: 60px;">Act.</div>
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
                                    <div class="list-group-item list-group-item-action p-2 d-flex align-items-center task-row" 
                                         onclick="showDetail(this, <?= htmlspecialchars(json_encode($t)) ?>, <?= htmlspecialchars(json_encode($subtasks_map[$t['id']] ?? [])) ?>)"
                                         style="cursor: pointer; transition: all 0.2s;">
                                        <!-- Checkbox Finish -->
                                        <div style="width: 30px;" onclick="event.stopPropagation()">
                                            <a href="tasks.php?toggle=<?= $t['id'] ?>" class="text-secondary hover-success"><i class="far fa-square fa-lg"></i></a>
                                        </div>
                                        <!-- BAdge -->
                                        <div style="width: 80px;">
                                            <?php if($t['importance']=='high'): ?>
                                                <span class="badge bg-danger rounded-pill">URGENT</span>
                                            <?php elseif($t['importance']=='low'): ?>
                                                <span class="badge bg-info text-dark rounded-pill">Basse</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border rounded-pill">Normale</span>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Chantier -->
                                        <div style="width: 150px;" class="small text-truncate text-muted pe-2">
                                            <?php 
                                            if($t['nom_affaire']) echo '<i class="fas fa-briefcase text-primary me-1"></i>' . htmlspecialchars($t['nom_affaire']); 
                                            elseif($t['ref_interne']) echo '<i class="fas fa-shopping-cart text-success me-1"></i>' . htmlspecialchars($t['ref_interne']);
                                            else echo '-';
                                            ?>
                                        </div>
                                        <!-- Titre -->
                                        <div class="flex-grow-1 fw-bold text-dark">
                                            <?= htmlspecialchars($t['title']) ?>
                                            <div class="small text-muted fw-normal text-truncate"><?= htmlspecialchars($t['description']) ?></div>
                                        </div>
                                        <!-- Subtasks count -->
                                        <div style="width: 50px;" class="text-center text-muted small">
                                            <span class="badge bg-light text-dark border"><i class="fas fa-list-ul me-1"></i><?= isset($subtasks_map[$t['id']]) ? count($subtasks_map[$t['id']]) : 0 ?></span>
                                        </div>
                                        <!-- Actions -->
                                        <div style="width: 60px;" class="text-end">
                                            <a href="#" class="text-primary me-2" onclick="editTask(event, <?= htmlspecialchars(json_encode($t)) ?>)"><i class="fas fa-edit"></i></a>
                                            <a href="tasks.php?del=<?= $t['id'] ?>" class="text-danger" onclick="return confirm('Confirmer ?'); event.stopPropagation();"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- COLONNE DROITE : DÉTAIL -->
                    <div class="col-md-5 bg-light d-flex flex-column">
                        <div id="detail-panel" class="p-4 flex-grow-1 overflow-auto">
                            <div class="text-center text-muted h-100 d-flex flex-column justify-content-center">
                                <i class="fas fa-mouse-pointer fa-3x mb-3 text-secondary opacity-50"></i>
                                <p>Cliquez sur une tâche pour voir les détails</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PANE 2: TERMINÉES -->
            <div class="tab-pane fade" id="done-pane">
                <div class="row g-0 border rounded shadow-sm overflow-hidden" style="min-height: 600px; background: #fff;">
                    
                    <!-- COLONNE GAUCHE : LISTE -->
                    <div class="col-md-7 border-end d-flex flex-column">
                        <!-- Header Tableau -->
                        <div class="bg-light p-2 border-bottom fw-bold text-muted small d-flex">
                            <div style="width: 30px;"></div>
                            <div style="width: 80px;">Priorité</div>
                            <div style="width: 150px;">Chantier</div>
                            <div class="flex-grow-1">Titre</div>
                            <div style="width: 50px;">Pts</div>
                            <div style="width: 60px;">Act.</div>
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
                                    <div class="list-group-item list-group-item-action p-2 d-flex align-items-center task-row bg-light" 
                                         onclick="showDetail(this, <?= htmlspecialchars(json_encode($t)) ?>, <?= htmlspecialchars(json_encode($subtasks_map[$t['id']] ?? [])) ?>)"
                                         style="cursor: pointer; transition: all 0.2s;">
                                        
                                        <!-- Checkbox (Restore) -->
                                        <div style="width: 30px;" onclick="event.stopPropagation()">
                                            <a href="tasks.php?toggle=<?= $t['id'] ?>" class="text-success hover-secondary"><i class="fas fa-check-square fa-lg"></i></a>
                                        </div>

                                        <!-- Priorité -->
                                        <div style="width: 80px; opacity: 0.6;">
                                            <?php if($t['importance']=='high'): ?>
                                                <span class="badge bg-danger rounded-pill">URGENT</span>
                                            <?php elseif($t['importance']=='low'): ?>
                                                <span class="badge bg-info text-dark rounded-pill">Basse</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border rounded-pill">Normale</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Chantier -->
                                        <div style="width: 150px; opacity: 0.6;" class="small text-truncate text-muted pe-2">
                                            <?php 
                                            if($t['nom_affaire']) echo htmlspecialchars($t['nom_affaire']); 
                                            elseif($t['ref_interne']) echo htmlspecialchars($t['ref_interne']);
                                            else echo '-';
                                            ?>
                                        </div>

                                        <!-- Titre (Barré) -->
                                        <div class="flex-grow-1 text-muted text-decoration-line-through">
                                            <?= htmlspecialchars($t['title']) ?>
                                        </div>

                                        <!-- Points -->
                                        <div style="width: 50px;" class="text-center text-muted small opacity-50">
                                            <i class="fas fa-list-ul me-1"></i><?= isset($subtasks_map[$t['id']]) ? count($subtasks_map[$t['id']]) : 0 ?>
                                        </div>

                                        <!-- Actions -->
                                        <div style="width: 60px;" class="text-end">
                                            <a href="tasks.php?del=<?= $t['id'] ?>" class="text-danger" onclick="return confirm('Supprimer DÉFINITIVEMENT ?'); event.stopPropagation();"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- COLONNE DROITE : DÉTAIL (PARTAGÉ) -->
                    <div class="col-md-5 bg-light d-flex flex-column">
                        <div id="detail-panel-done" class="p-4 flex-grow-1 overflow-auto detail-container">
                            <!-- NOTE: On utilisera la même fonction showDetail, mais il faut cibler le bon conteneur. 
                                 Pour simplifier, on utilisera un SEUL ID de conteneur global si possible, ou on adaptera le JS.
                                 ACTUELLEMENT le JS cible 'detail-panel'. Ici j'ai mis 'detail-panel-done'.
                                 Je vais modifier le JS pour qu'il cible dynamiquement ou unifier les ID.
                                 
                                 Correction rapide : J'utilise le MÊME ID 'detail-panel' mais WARNING : les IDs doivent être uniques.
                                 Comme c'est des onglets, seul un est visible. Mais c'est crade.
                                 Mieux : J'utilise une classe ou je modifie le JS pour cibler le bon panel.
                            -->
                            <div class="text-center text-muted h-100 d-flex flex-column justify-content-center">
                                <i class="fas fa-mouse-pointer fa-3x mb-3 text-secondary opacity-50"></i>
                                <p>Cliquez sur une tâche pour voir les détails</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>


        </div>
    </div>
</div>

<!-- MODAL (Z-INDEX 99999) -->
<div class="modal fade" id="addTaskModal" tabindex="-1" style="z-index: 99999;">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 100000;">
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
                            <label class="form-label">Echéance</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Chantier (Affaire)</label>
                        <select name="affaire_id" class="form-select">
                            <option value="">-- Aucun --</option>
                            <?php foreach($affaires_list as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nom_affaire']) ?></option>
                            <?php endforeach; ?>
                        </select>
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

<script>
// Open Modal (Add Mode)
function openAddTaskModal() {
    document.querySelector('#addTaskModal .modal-title').textContent = 'Nouvelle Tâche';
    document.querySelector('#addTaskModal input[name="action"]').value = 'add_task';
    document.querySelector('#addTaskModal input[name="title"]').value = '';
    document.querySelector('#addTaskModal textarea[name="description"]').value = '';
    document.querySelector('#addTaskModal select[name="importance"]').value = 'normal';
    document.querySelector('#addTaskModal select[name="affaire_id"]').value = '';
    
    // Reset ID hidden field if exists or create it
    let idField = document.querySelector('#addTaskModal input[name="task_id"]');
    if(idField) idField.value = '';

    new bootstrap.Modal(document.getElementById('addTaskModal')).show();
}

// Open Modal (Edit Mode)
function editTask(event, task) {
    event.stopPropagation(); // Stop click form opening details
    
    document.querySelector('#addTaskModal .modal-title').textContent = 'Modifier Tâche';
    document.querySelector('#addTaskModal input[name="action"]').value = 'edit_task'; // Need to handle this in PHP
    document.querySelector('#addTaskModal input[name="title"]').value = task.title;
    document.querySelector('#addTaskModal textarea[name="description"]').value = task.description;
    document.querySelector('#addTaskModal select[name="importance"]').value = task.importance;
    document.querySelector('#addTaskModal select[name="affaire_id"]').value = task.affaire_id || '';
    
    // Add Hidden ID
    let form = document.querySelector('#addTaskModal form');
    let idField = form.querySelector('input[name="task_id"]');
    if(!idField) {
        idField = document.createElement('input');
        idField.type = 'hidden';
        idField.name = 'task_id';
        form.appendChild(idField);
    }
    idField.value = task.id;

    new bootstrap.Modal(document.getElementById('addTaskModal')).show();
}

// Show Details
function showDetail(element, task, subtasks) {
    // Highlight Row (Global reset)
    document.querySelectorAll('.task-row').forEach(el => {
        el.classList.remove('bg-primary-subtle');
        el.style.borderLeft = 'none';
    });
    element.classList.add('bg-primary-subtle');
    element.style.borderLeft = '4px solid #0d6efd';

    // Determine Logic Container
    // If I am in todo-pane, target detail-panel. If in done-pane, target detail-panel-done.
    let targetId = 'detail-panel';
    if(document.getElementById('done-pane').classList.contains('active')) {
        targetId = 'detail-panel-done';
    }
    let container = document.getElementById(targetId);
    if(!container) container = document.getElementById('detail-panel'); // Fallback

    // Build Detail HTML
    let html = `
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h4 class="fw-bold text-dark mb-0">${task.title}</h4>
            <span class="badge bg-${task.importance=='high'?'danger':'secondary'}">${task.importance=='high'?'URGENT':'Normal'}</span>
        </div>
        
        <p class="text-secondary bg-white p-3 rounded border mb-4">${task.description || '<i>Aucune description</i>'}</p>
        
        <h6 class="fw-bold text-primary mb-3"><i class="fas fa-tasks me-2"></i>Sous-tâches</h6>
        
        <ul class="list-group mb-3 border-0 bg-transparent" id="subtasks-list-${task.id}">
    `;

    if (subtasks.length === 0) {
        html += '<li class="list-group-item bg-transparent text-muted small border-0">Aucune sous-tâche pour le moment.</li>';
    } else {
        subtasks.forEach(item => {
            let checked = item.is_completed == 1 ? 'checked' : '';
            let style = item.is_completed == 1 ? 'text-decoration: line-through; opacity: 0.5;' : '';
            
            // AJAX Toggle with Edit/Delete
            html += `
                <li class="list-group-item bg-transparent border-0 border-bottom d-flex align-items-center gap-2">
                    <input type="checkbox" class="form-check-input mt-0" ${checked} 
                           onchange="toggleSubtask(this, ${item.id})" style="cursor: pointer;">
                    
                    <span style="${style}" class="flex-grow-1" id="subtext-${item.id}">${item.content}</span>
                    
                    <a href="#" onclick="editSubtask(${item.id}, '${item.content.replace(/'/g, "\\'")}')" class="text-primary small me-2"><i class="fas fa-edit"></i></a>
                    <a href="tasks.php?del_sub=${item.id}" onclick="return confirm('Supprimer cette étape ?')" class="text-danger small"><i class="fas fa-trash"></i></a>
                </li>
            `;
        });
    }

    html += `</ul>
        
        <!-- Add Subtask -->
        <div class="input-group">
            <input type="text" id="new-sub-${task.id}" class="form-control" placeholder="Ajouter une étape...">
            <button class="btn btn-primary" onclick="addSubtask(${task.id})"><i class="fas fa-plus"></i></button>
            <!-- Hidden refresh button trick -->
            <a href="tasks.php" id="refresh-page" style="display:none"></a>
        </div>
    `;

    container.innerHTML = html;
}

// AJAX SUBTASK TOGGLE
function toggleSubtask(checkbox, subtaskId) {
    let span = checkbox.nextElementSibling; // The Span is next to checkbox? No, verify DOM structure above
    // Structure is: Checkbox -> Span -> Edit -> Delete. Yes nextElementSibling works for Span.
    if(checkbox.checked) {
        span.style.textDecoration = 'line-through';
        span.style.opacity = '0.5';
    } else {
        span.style.textDecoration = 'none';
        span.style.opacity = '1';
    }
    fetch('tasks.php?toggle_sub=' + subtaskId);
}

function editSubtask(subId, currentContent) {
    let newContent = prompt("Modifier l'étape :", currentContent);
    if (newContent !== null && newContent.trim() !== "") {
        window.location.href = `tasks.php?edit_sub=1&sub_id=${subId}&content=${encodeURIComponent(newContent)}`;
    }
}

function addSubtask(taskId) {
    const content = document.getElementById(`new-sub-${taskId}`).value;
    if(content) window.location.href = `tasks.php?add_sub=1&task_id=${taskId}&content=${encodeURIComponent(content)}`;
}
</script>

<?php
// ACTIONS PHP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add_task') {
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, importance, due_date, status, affaire_id, created_at) VALUES (?, ?, ?, ?, ?, 'todo', ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], $_POST['title'], $_POST['description'], $_POST['importance'], $_POST['due_date'], $_POST['affaire_id'] ?: null]);
    }
    elseif ($_POST['action'] === 'edit_task') {
        $stmt = $pdo->prepare("UPDATE tasks SET title=?, description=?, importance=?, affaire_id=? WHERE id=?");
        $stmt->execute([$_POST['title'], $_POST['description'], $_POST['importance'], $_POST['affaire_id'] ?: null, $_POST['task_id']]);
    }
    echo "<script>window.location.href='tasks.php';</script>";
}
if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE tasks SET status = IF(status='done','todo','done') WHERE id=?")->execute([$_GET['toggle']]);
    echo "<script>window.location.href='tasks.php';</script>";
}
if (isset($_GET['del'])) {
    $pdo->prepare("DELETE FROM tasks WHERE id=?")->execute([$_GET['del']]);
    echo "<script>window.location.href='tasks.php';</script>";
}
if (isset($_GET['add_sub'])) {
    $pdo->prepare("INSERT INTO task_items (task_id, content, is_completed) VALUES (?, ?, 0)")->execute([$_GET['task_id'], $_GET['content']]);
    echo "<script>window.location.href='tasks.php';</script>";
}
if (isset($_GET['edit_sub'])) {
    $pdo->prepare("UPDATE task_items SET content=? WHERE id=?")->execute([$_GET['content'], $_GET['sub_id']]);
    echo "<script>window.location.href='tasks.php';</script>";
}
if (isset($_GET['toggle_sub'])) {
    $pdo->prepare("UPDATE task_items SET is_completed = NOT is_completed WHERE id=?")->execute([$_GET['toggle_sub']]);
    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') exit;
}
if (isset($_GET['del_sub'])) {
    $pdo->prepare("DELETE FROM task_items WHERE id=?")->execute([$_GET['del_sub']]);
    echo "<script>window.location.href='tasks.php';</script>";
}
?>

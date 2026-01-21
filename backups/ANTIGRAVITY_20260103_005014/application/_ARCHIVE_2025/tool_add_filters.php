<?php
// Add filters above task list
$file = 'tasks.php';
$content = file_get_contents($file);

// Find where to insert filters (after the "Nouvelle Tâche" button, before tabs)
$search = <<<'SEARCH'
    </div>

    <!-- TABS NAVIGATION -->
SEARCH;

$replace = <<<'REPLACE'
    </div>

    <!-- FILTERS -->
    <div class="row g-2 mb-3 mt-2">
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-muted">Priorité</label>
            <select id="filter-importance" class="form-select form-select-sm">
                <option value="">Toutes</option>
                <option value="high">Urgente</option>
                <option value="normal">Normale</option>
                <option value="low">Basse</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold text-muted">Client / Chantier</label>
            <input type="text" id="filter-chantier" class="form-control form-control-sm" placeholder="Rechercher...">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-sm btn-outline-secondary w-100" onclick="resetFilters()">
                <i class="fas fa-redo me-1"></i>Réinitialiser
            </button>
        </div>
    </div>

    <!-- TABS NAVIGATION -->
REPLACE;

$content = str_replace($search, $replace, $content);

// Add JavaScript for filtering at the end of the script section
$search_js = 'function toggleSubtask(id) {
    window.location.href = \'tasks.php?toggle_subtask_id=\' + id;
}';

$replace_js = <<<'REPLACE'
function toggleSubtask(id) {
    window.location.href = 'tasks.php?toggle_subtask_id=' + id;
}

// Filter functions
function resetFilters() {
    document.getElementById('filter-importance').value = '';
    document.getElementById('filter-chantier').value = '';
    applyFilters();
}

function applyFilters() {
    const importanceFilter = document.getElementById('filter-importance').value.toLowerCase();
    const chantierFilter = document.getElementById('filter-chantier').value.toLowerCase();
    const taskItems = document.querySelectorAll('.task-item');
    
    taskItems.forEach(item => {
        let show = true;
        
        // Filter by importance
        if (importanceFilter) {
            const taskData = JSON.parse(item.dataset.taskData || '{}');
            if (taskData.importance !== importanceFilter) {
                show = false;
            }
        }
        
        // Filter by chantier (search in affaire name or ref_interne)
        if (chantierFilter) {
            const taskData = JSON.parse(item.dataset.taskData || '{}');
            const affaireName = (taskData.nom_affaire || '').toLowerCase();
            const refInterne = (taskData.ref_interne || '').toLowerCase();
            if (!affaireName.includes(chantierFilter) && !refInterne.includes(chantierFilter)) {
                show = false;
            }
        }
        
        item.style.display = show ? '' : 'none';
    });
}

// Attach filter listeners
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filter-importance').addEventListener('change', applyFilters);
    document.getElementById('filter-chantier').addEventListener('input', applyFilters);
});
REPLACE;

$content = str_replace($search_js, $replace_js, $content);

// Add task data to each task item for filtering
$search_task = '<li class="list-group-item p-2 hover-bg-light transition-base task-item" 
                                            data-task-id="<?= $t[\'id\'] ?>" 
                                            data-subtasks=\'<?= json_encode($t[\'subtasks\']) ?>\'
                                            style="cursor: pointer;">';

$replace_task = '<li class="list-group-item p-2 hover-bg-light transition-base task-item" 
                                            data-task-id="<?= $t[\'id\'] ?>" 
                                            data-subtasks=\'<?= json_encode($t[\'subtasks\']) ?>\'
                                            data-task-data=\'<?= json_encode([\'importance\' => $t[\'importance\'], \'nom_affaire\' => $t[\'nom_affaire\'], \'ref_interne\' => $t[\'ref_interne\']]) ?>\'
                                            style="cursor: pointer;">';

$content = str_replace($search_task, $replace_task, $content);

file_put_contents($file, $content);
echo "Filters added successfully!\n";
?>

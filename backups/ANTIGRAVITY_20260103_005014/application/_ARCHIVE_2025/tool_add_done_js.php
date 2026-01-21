<?php
// Add JavaScript handlers for completed tasks panel
$file = 'tasks.php';
$content = file_get_contents($file);

// Find the existing JavaScript section and add handlers for done tasks
$search = <<<'SEARCH'
    // Auto-select task if selected_task parameter exists
    const urlParams = new URLSearchParams(window.location.search);
    const selectedTaskId = urlParams.get('selected_task');
    if (selectedTaskId) {
        const taskToSelect = document.querySelector(`.task-item[data-task-id="${selectedTaskId}"]`);
        if (taskToSelect) {
            taskToSelect.click();
        }
    }
    
    taskItems.forEach(item => {
SEARCH;

$replace = <<<'REPLACE'
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
REPLACE;

$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "JavaScript handlers added for completed tasks!\n";
?>

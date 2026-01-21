<?php
// Fix bugs: persist selected task after checkbox toggle, fix modal functions
$file = 'tasks.php';
$content = file_get_contents($file);

// 1. Add session storage to remember selected task
$search1 = <<<'SEARCH'
// 5. TOGGLE SOUS-TÂCHE (cocher/décocher)
if (isset($_GET['toggle_subtask_id'])) {
    $subtask_id = intval($_GET['toggle_subtask_id']);
    // Verify ownership and toggle
    $stmt = $pdo->prepare("UPDATE task_items ti 
                           INNER JOIN tasks t ON ti.task_id = t.id 
                           SET ti.is_completed = NOT ti.is_completed 
                           WHERE ti.id = ? AND t.user_id = ?");
    $stmt->execute([$subtask_id, $_SESSION['user_id']]);
    header("Location: tasks.php");
    exit;
}
SEARCH;

$replace1 = <<<'REPLACE'
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
REPLACE;

$content = str_replace($search1, $replace1, $content);

// 2. Update checkbox onclick to include selected_task parameter
$search2 = <<<'SEARCH'
                                <input type="checkbox" ${checked} class="form-check-input m-0" 
                                       style="width: 20px; height: 20px; border-width: 2px; cursor: pointer;"
                                       onclick="window.location.href='tasks.php?toggle_subtask_id=${sub.id}'">
                                <span class="${textClass}" style="cursor: pointer;" 
                                      onclick="window.location.href='tasks.php?toggle_subtask_id=${sub.id}'">${sub.content}</span>
SEARCH;

$replace2 = <<<'REPLACE'
                                <input type="checkbox" ${checked} class="form-check-input m-0" 
                                       style="width: 20px; height: 20px; border-width: 2px; cursor: pointer;"
                                       onclick="window.location.href='tasks.php?toggle_subtask_id=${sub.id}&selected_task=${taskId}'">
                                <span class="${textClass}" style="cursor: pointer;" 
                                      onclick="window.location.href='tasks.php?toggle_subtask_id=${sub.id}&selected_task=${taskId}'">${sub.content}</span>
REPLACE;

$content = str_replace($search2, $replace2, $content);

// 3. Add auto-selection of task if selected_task parameter exists
$search3 = <<<'SEARCH'
// Handle task selection and subtask display
document.addEventListener('DOMContentLoaded', function() {
    const taskItems = document.querySelectorAll('.task-item');
    const subtasksPanel = document.getElementById('subtasks-panel');
SEARCH;

$replace3 = <<<'REPLACE'
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
REPLACE;

$content = str_replace($search3, $replace3, $content);

file_put_contents($file, $content);
echo "Bugs fixed: subtasks persist after checkbox toggle!\n";
?>

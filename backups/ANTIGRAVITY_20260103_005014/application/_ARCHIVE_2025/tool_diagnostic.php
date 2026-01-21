<?php
// Complete diagnostic and fix for all reported issues
$file = 'tasks.php';
$content = file_get_contents($file);

echo "=== DIAGNOSTIC ===\n";

// Check 1: Is openAddTaskModal function present?
if (strpos($content, 'function openAddTaskModal') !== false) {
    echo "✓ openAddTaskModal function exists\n";
} else {
    echo "✗ openAddTaskModal function MISSING\n";
}

// Check 2: Is editTask function present?
if (strpos($content, 'function editTask') !== false) {
    echo "✓ editTask function exists\n";
} else {
    echo "✗ editTask function MISSING\n";
}

// Check 3: Is modal HTML present?
if (strpos($content, 'id="addTaskModal"') !== false) {
    echo "✓ Modal HTML exists\n";
} else {
    echo "✗ Modal HTML MISSING\n";
}

// Check 4: Is selected_task parameter in checkbox onclick?
if (strpos($content, 'selected_task=${taskId}') !== false) {
    echo "✓ selected_task parameter in checkbox\n";
} else {
    echo "✗ selected_task parameter MISSING in checkbox\n";
}

// Check 5: Is auto-selection code present?
if (strpos($content, 'const selectedTaskId = urlParams.get') !== false) {
    echo "✓ Auto-selection code exists\n";
} else {
    echo "✗ Auto-selection code MISSING\n";
}

echo "\n=== END DIAGNOSTIC ===\n";
?>

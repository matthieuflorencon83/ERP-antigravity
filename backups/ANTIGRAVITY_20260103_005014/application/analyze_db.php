<?php
require 'db.php';

echo "=== ANALYSE BASE DE DONNÉES ===\n\n";

// 1. Structure table tasks
echo "TABLE: tasks\n";
echo str_repeat("-", 80) . "\n";
$stmt = $pdo->query('DESCRIBE tasks');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    printf("%-20s | %-20s | %-5s | %-5s\n", 
        $row['Field'], $row['Type'], $row['Null'], $row['Key']);
}

echo "\n\nTABLE: task_items\n";
echo str_repeat("-", 80) . "\n";
$stmt = $pdo->query('DESCRIBE task_items');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    printf("%-20s | %-20s | %-5s | %-5s\n", 
        $row['Field'], $row['Type'], $row['Null'], $row['Key']);
}

// 2. Données existantes
echo "\n\n=== DONNÉES EXISTANTES ===\n\n";
$stmt = $pdo->query('SELECT COUNT(*) as total, status FROM tasks GROUP BY status');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Status '{$row['status']}': {$row['total']} tâches\n";
}

// 3. Exemple de tâche avec sous-tâches
echo "\n\n=== EXEMPLE DE TÂCHE ===\n\n";
$stmt = $pdo->query('SELECT * FROM tasks LIMIT 1');
$task = $stmt->fetch(PDO::FETCH_ASSOC);
if ($task) {
    print_r($task);
    
    echo "\nSous-tâches:\n";
    $stmt2 = $pdo->prepare('SELECT * FROM task_items WHERE task_id = ?');
    $stmt2->execute([$task['id']]);
    while($item = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        print_r($item);
    }
}
?>

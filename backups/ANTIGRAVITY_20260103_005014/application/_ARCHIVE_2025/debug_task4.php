<?php
require 'db.php';
require 'functions.php';
session_start();

$user_id = $_SESSION['user_id'];

// Vérifier la tâche ID 4
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = 4 AND user_id = ?");
$stmt->execute([$user_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

echo "=== TÂCHE ID 4 ===\n";
print_r($task);

// Vérifier les sous-tâches
$stmt = $pdo->prepare("SELECT * FROM task_items WHERE task_id = 4");
$stmt->execute();
$subtasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\n=== SOUS-TÂCHES ===\n";
print_r($subtasks);

echo "\n=== JSON ENCODÉ ===\n";
echo json_encode($subtasks);
?>

<?php
require 'db.php';
require 'functions.php';
session_start();

$user_id = $_SESSION['user_id'];

// Create a test task in "todo" status
$stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, importance, status, created_at) VALUES (?, ?, ?, ?, 'todo', NOW())");
$stmt->execute([$user_id, 'Test - Vérifier interface', 'Tâche de test pour vérifier que l\'interface fonctionne correctement', 'high']);
$task_id = $pdo->lastInsertId();

// Add some subtasks
$stmt = $pdo->prepare("INSERT INTO task_items (task_id, content, is_completed) VALUES (?, ?, ?)");
$stmt->execute([$task_id, 'Vérifier le bandeau d\'en-tête', 0]);
$stmt->execute([$task_id, 'Tester les filtres', 0]);
$stmt->execute([$task_id, 'Cliquer sur une tâche pour voir les sous-tâches', 0]);

echo "Test task created with ID: $task_id\n";
echo "Go to tasks.php to see it in the 'En cours' tab!\n";
?>

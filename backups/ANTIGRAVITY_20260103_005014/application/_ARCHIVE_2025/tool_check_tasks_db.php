<?php
require 'db.php';
require 'functions.php';
session_start();

if(!isset($_SESSION['user_id'])) {
    die("Not logged in\n");
}

$user_id = $_SESSION['user_id'];
echo "User ID: $user_id\n\n";

// Check tasks
$stmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE user_id = $user_id");
$count = $stmt->fetchColumn();
echo "Tasks count: $count\n\n";

if ($count > 0) {
    $stmt = $pdo->query("SELECT id, title, status FROM tasks WHERE user_id = $user_id LIMIT 5");
    echo "Sample tasks:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
}
?>

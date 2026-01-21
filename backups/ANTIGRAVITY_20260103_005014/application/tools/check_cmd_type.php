<?php
require_once __DIR__ . '/../db.php';

// Check if Table or View
$stmt = $pdo->query("SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'antigravity' AND TABLE_NAME = 'commandes_achats'");
echo "Type: " . $stmt->fetchColumn() . "\n";

// List Cols
$stmt = $pdo->query("DESCRIBE commandes_achats");
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo $col['Field'] . "\n";
}

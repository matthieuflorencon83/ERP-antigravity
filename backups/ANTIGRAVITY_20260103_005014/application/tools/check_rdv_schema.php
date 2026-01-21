<?php
require_once __DIR__ . '/../db.php';
// Scan rendez_vous table
echo "<h2>Table: rendez_vous</h2>";
try {
    $stmt = $pdo->query("DESCRIBE rendez_vous");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<pre>" . print_r($columns, true) . "</pre>";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

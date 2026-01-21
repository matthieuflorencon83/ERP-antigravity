<?php
require_once __DIR__ . '/../db.php';
// Scan Clients table
echo "<h2>Table: clients</h2>";
try {
    $stmt = $pdo->query("DESCRIBE clients");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<pre>" . print_r($columns, true) . "</pre>";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

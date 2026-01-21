<?php
require_once __DIR__ . '/../db.php';
// Scan ARTICLES table
echo "<h2>Table: articles</h2>";
try {
    $stmt = $pdo->query("DESCRIBE articles");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<pre>" . print_r($columns, true) . "</pre>";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

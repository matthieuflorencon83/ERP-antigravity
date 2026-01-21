<?php
require_once __DIR__ . '/../db.php';
// Scan SAV_TICKETS table
echo "<h2>Table: sav_tickets</h2>";
try {
    $stmt = $pdo->query("DESCRIBE sav_tickets");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<pre>" . print_r($columns, true) . "</pre>";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

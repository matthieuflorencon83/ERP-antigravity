<?php
require_once __DIR__ . '/../db.php';
// Scan restored SAV table
echo "<h2>Restored Schema: sav_interventions</h2>";
try {
    $stmt = $pdo->query("DESCRIBE sav_interventions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<pre>" . print_r($columns, true) . "</pre>";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

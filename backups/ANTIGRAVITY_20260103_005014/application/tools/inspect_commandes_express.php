<?php
// tools/inspect_commandes_express.php
require_once __DIR__ . '/../db.php';

try {
    echo "Inspecting commandes_express...\n";
    $stmt = $pdo->query("DESCRIBE commandes_express");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        echo "Field: " . $col['Field'] . " | Type: " . $col['Type'] . " | Null: " . $col['Null'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

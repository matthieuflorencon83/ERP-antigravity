<?php
// tools/inspect_commandes_achats.php
require_once __DIR__ . '/../db.php';

try {
    echo "Inspecting commandes_achats...\n";
    $stmt = $pdo->query("DESCRIBE commandes_achats");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        echo "Field: " . $col['Field'] . " | Type: " . $col['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

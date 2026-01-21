<?php
// tools/audit_schema.php
require_once __DIR__ . '/../db.php';

try {
    echo "Inspecting commandes_express...\n";
    $stmt = $pdo->query("DESCRIBE commandes_express");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        printf("%-20s | %-15s | %s\n", $col['Field'], $col['Type'], $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL');
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

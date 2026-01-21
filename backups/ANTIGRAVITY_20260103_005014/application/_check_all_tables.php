<?php
require_once 'auth.php';

echo "=== RECHERCHE TABLES LISTE DE BESOIN ===\n\n";

// 1. All tables
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "All tables in database:\n";
    foreach ($tables as $table) {
        if (stripos($table, 'besoin') !== false || stripos($table, 'liste') !== false || stripos($table, 'debit') !== false) {
            echo "  *** $table ***\n";
        } else {
            echo "  - $table\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// 2. Check besoins_lignes
try {
    $stmt = $pdo->query("DESCRIBE besoins_lignes");
    $columns = $stmt->fetchAll();
    echo "Table 'besoins_lignes' structure:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM besoins_lignes");
    $count = $stmt->fetch();
    echo "Total rows in besoins_lignes: " . $count['total'] . "\n\n";
    
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM besoins_lignes LIMIT 5");
        $samples = $stmt->fetchAll();
        echo "Sample data:\n";
        foreach ($samples as $row) {
            print_r($row);
            echo "\n";
        }
    }
} catch (Exception $e) {
    echo "Error with besoins_lignes: " . $e->getMessage() . "\n\n";
}

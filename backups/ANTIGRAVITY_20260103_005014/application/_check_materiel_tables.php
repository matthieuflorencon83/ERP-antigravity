<?php
require_once 'auth.php';

echo "=== ANALYSE TABLES MATÉRIEL ===\n\n";

// 1. Check all tables
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables avec 'stock', 'materiel', 'mouvement':\n";
    foreach ($tables as $table) {
        if (stripos($table, 'stock') !== false || 
            stripos($table, 'materiel') !== false || 
            stripos($table, 'mouvement') !== false ||
            stripos($table, 'article') !== false) {
            echo "  *** $table ***\n";
            
            // Show structure
            $stmt_desc = $pdo->query("DESCRIBE `$table`");
            $cols = $stmt_desc->fetchAll();
            echo "      Colonnes: ";
            $colNames = array_map(function($c) { return $c['Field']; }, $cols);
            echo implode(', ', $colNames) . "\n";
            
            // Count rows
            $stmt_count = $pdo->query("SELECT COUNT(*) as total FROM `$table`");
            $count = $stmt_count->fetch();
            echo "      Lignes: {$count['total']}\n\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// 2. Check stocks_mouvements structure
echo "\n=== STRUCTURE stocks_mouvements ===\n";
try {
    $stmt = $pdo->query("DESCRIBE stocks_mouvements");
    $cols = $stmt->fetchAll();
    foreach ($cols as $col) {
        echo "  - {$col['Field']} ({$col['Type']}) " . ($col['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
    
    // Sample data
    echo "\n=== SAMPLE stocks_mouvements ===\n";
    $stmt = $pdo->query("SELECT * FROM stocks_mouvements LIMIT 3");
    $samples = $stmt->fetchAll();
    if (count($samples) > 0) {
        foreach ($samples as $s) {
            echo "  ID: {$s['id']}, Affaire: {$s['affaire_id']}, Article: {$s['article_id']}, Type: {$s['type_mouvement']}, Qte: {$s['quantite']}\n";
        }
    } else {
        echo "  (aucune donnée)\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// 3. Check if there's a dedicated materiel table
echo "\n=== RECHERCHE TABLE DÉDIÉE ===\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE '%materiel%'");
    $materiel_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($materiel_tables) > 0) {
        echo "Tables trouvées:\n";
        foreach ($materiel_tables as $t) {
            echo "  - $t\n";
        }
    } else {
        echo "Aucune table dédiée 'materiel' trouvée.\n";
        echo "RECOMMANDATION: Créer une nouvelle table 'affaires_materiel' pour le pense-bête.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

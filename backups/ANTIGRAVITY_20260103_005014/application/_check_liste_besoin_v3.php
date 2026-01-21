<?php
require_once 'auth.php';

echo "=== RECHERCHE LISTES DE BESOIN V3 ===\n\n";

// 1. Check for liste_besoin or similar tables
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables contenant 'liste' ou 'besoin':\n";
    foreach ($tables as $table) {
        if (stripos($table, 'liste') !== false || stripos($table, 'besoin') !== false) {
            echo "  *** $table ***\n";
            
            // Count rows
            $stmt_count = $pdo->query("SELECT COUNT(*) as total FROM `$table`");
            $count = $stmt_count->fetch();
            echo "      -> {$count['total']} lignes\n";
            
            // Show structure
            $stmt_desc = $pdo->query("DESCRIBE `$table`");
            $cols = $stmt_desc->fetchAll();
            echo "      -> Colonnes: ";
            $colNames = array_map(function($c) { return $c['Field']; }, $cols);
            echo implode(', ', $colNames) . "\n\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// 2. Check besoins_lignes with affaire_id filter
echo "\n=== BESOINS_LIGNES pour affaire 999 ===\n";
try {
    $stmt = $pdo->prepare("SELECT * FROM besoins_lignes WHERE affaire_id = ?");
    $stmt->execute([999]);
    $besoins = $stmt->fetchAll();
    echo "Nombre de lignes: " . count($besoins) . "\n";
    if (count($besoins) > 0) {
        foreach ($besoins as $b) {
            print_r($b);
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// 3. Sample from besoins_lignes (any affaire)
echo "\n=== SAMPLE BESOINS_LIGNES (toutes affaires) ===\n";
try {
    $stmt = $pdo->query("SELECT * FROM besoins_lignes LIMIT 10");
    $samples = $stmt->fetchAll();
    echo "Nombre de lignes: " . count($samples) . "\n";
    if (count($samples) > 0) {
        foreach ($samples as $s) {
            echo "ID: {$s['id']}, Affaire: {$s['affaire_id']}, Designation: {$s['designation_besoin']}, Qte: {$s['quantite_brute']}\n";
        }
    } else {
        echo "(aucune donnée)\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

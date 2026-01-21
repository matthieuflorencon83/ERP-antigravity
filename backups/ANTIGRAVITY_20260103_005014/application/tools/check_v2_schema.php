<?php
// tools/check_v2_schema.php
require_once __DIR__ . '/../db.php';

echo "Checking 'besoins_lignes' table...\n";
try {
    $pdo->query("SELECT 1 FROM besoins_lignes LIMIT 1");
    echo "EXISTS.\n";
} catch (PDOException $e) {
    echo "MISSING (" . $e->getMessage() . ")\n";
}

echo "\nChecking 'articles_catalogue' columns...\n";
try {
    $stmt = $pdo->query("DESCRIBE articles_catalogue");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $newCols = ['longueurs_possibles_json', 'poids_metre_lineaire', 'inertie_lx', 'articles_lies_json'];
    
    foreach ($newCols as $c) {
        if (in_array($c, $cols)) {
            echo "$c: OK\n";
        } else {
            echo "$c: MISSING\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

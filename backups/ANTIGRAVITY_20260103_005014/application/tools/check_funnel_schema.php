<?php
// tools/check_funnel_schema.php
require_once __DIR__ . '/../db.php';

function checkTable($pdo, $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        echo "Table '$table': EXISTS (" . $stmt->fetchColumn() . " rows)\n";
        
        $stmt = $pdo->query("DESCRIBE $table");
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "  Columns: " . implode(', ', $cols) . "\n";
    } catch (PDOException $e) {
        echo "Table '$table': MISSING or Error (" . $e->getMessage() . ")\n";
    }
}

echo "--- CHECKING SCHEMA FOR FUNNEL V3 ---\n";
checkTable($pdo, 'fournisseurs');
checkTable($pdo, 'familles_articles'); // Checked previously, but good to confirm
checkTable($pdo, 'sous_familles_articles'); 
checkTable($pdo, 'articles_catalogue');

echo "\n--- CHECKING RELATIONS ---\n";
// Check if articles_catalogue has fournisseur_id and sous_famille_id
try {
    $stmt = $pdo->query("DESCRIBE articles_catalogue");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $rels = ['fournisseur_id', 'famille_id', 'sous_famille_id', 'image_path'];
    foreach ($rels as $r) {
        echo "Column '$r' in 'articles_catalogue': " . (in_array($r, $cols) ? "OK" : "MISSING") . "\n";
    }
} catch (Exception $e) {}

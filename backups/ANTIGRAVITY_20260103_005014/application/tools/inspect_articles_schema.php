<?php
// tools/inspect_articles_schema.php
require_once __DIR__ . '/../db.php';

echo "=== STRUCTURE TABLE ARTICLES ===\n\n";
$stmt = $pdo->query("DESCRIBE articles");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($cols as $col) {
    echo "{$col['Field']} | {$col['Type']} | {$col['Null']} | {$col['Key']} | {$col['Default']}\n";
}

echo "\n=== FOREIGN KEYS ===\n";
$stmt = $pdo->query("
    SELECT 
        CONSTRAINT_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'antigravity' 
    AND TABLE_NAME = 'articles'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");

$fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(count($fks) > 0) {
    foreach($fks as $fk) {
        echo "{$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
    }
} else {
    echo "Aucune Foreign Key définie.\n";
}

echo "\n=== SAMPLE DATA ===\n";
$stmt = $pdo->query("SELECT id, reference_interne, designation, fournisseur_prefere_id FROM articles LIMIT 3");
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
    echo "ID {$a['id']}: {$a['designation']} (Fournisseur ID: {$a['fournisseur_prefere_id']})\n";
}

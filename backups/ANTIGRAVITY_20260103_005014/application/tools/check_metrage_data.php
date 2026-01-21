<?php
require_once __DIR__ . '/../db.php';

echo "CHECKING METRAGE DATA...\n";

$types = $pdo->query("SELECT count(*) FROM metrage_types")->fetchColumn();
echo "Types Count: $types (Expected 16)\n";

$points = $pdo->query("SELECT count(*) FROM metrage_points_controle")->fetchColumn();
echo "Points Count: $points\n";

$nulls = $pdo->query("SELECT count(*) FROM metrage_points_controle WHERE type_saisie IS NULL")->fetchColumn();
echo "Points with NULL type_saisie: $nulls\n";

if ($nulls > 0) {
    echo "⚠️ FIXING NULL TYPES...\n";
    $pdo->exec("UPDATE metrage_points_controle SET type_saisie = 'mm' WHERE type_saisie IS NULL");
    echo "✅ Fixed.\n";
}

$samples = $pdo->query("SELECT t.nom, p.label, p.type_saisie FROM metrage_points_controle p JOIN metrage_types t ON p.metrage_type_id = t.id LIMIT 5");
foreach($samples as $s) {
    echo "{$s['nom']} | {$s['label']} | {$s['type_saisie']}\n";
}

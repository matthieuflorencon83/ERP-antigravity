<?php
// tools/check_fournisseurs_structure.php
require_once __DIR__ . '/../db.php';

echo "=== STRUCTURE TABLE FOURNISSEURS ===\n\n";
$stmt = $pdo->query("DESCRIBE fournisseurs");
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "{$col['Field']} | {$col['Type']} | {$col['Key']}\n";
}

echo "\n=== DONNÉES FOURNISSEURS ===\n";
$stmt = $pdo->query("SELECT id, code_fou, nom FROM fournisseurs");
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
    echo "ID: {$f['id']} | code_fou: {$f['code_fou']} | Nom: {$f['nom']}\n";
}

echo "\n=== RELATION ACTUELLE DANS ARTICLES ===\n";
$stmt = $pdo->query("
    SELECT a.id, a.designation, a.fournisseur_prefere_id, f.code_fou, f.nom
    FROM articles a
    LEFT JOIN fournisseurs f ON a.fournisseur_prefere_id = f.id
    LIMIT 5
");
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "Article: {$row['designation']} → Fournisseur ID: {$row['fournisseur_prefere_id']} (code_fou: {$row['code_fou']}, {$row['nom']})\n";
}

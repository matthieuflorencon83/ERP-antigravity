<?php
// tools/seed_finitions.php
require_once __DIR__ . '/../db.php';

$colors = [
    ['9005', 'Noir Profond', 'Mat Texturé'],
    ['9016', 'Blanc Trafic', 'Satiné'],
    ['1247', 'Bronze', 'Satiné'],
    ['7035', 'Gris Clair', 'Satiné'],
    ['3004', 'Rouge Pourpre', 'Mat']
];

$stmt = $pdo->prepare("INSERT INTO finitions (code_ral, nom_couleur, aspect) VALUES (?, ?, ?)");

foreach ($colors as $c) {
    // Check exist
    if (!$pdo->query("SELECT count(*) FROM finitions WHERE code_ral = '{$c[0]}' AND aspect = '{$c[2]}'")->fetchColumn()) {
        $stmt->execute($c);
        echo "Inserted: {$c[0]} {$c[2]}<br>";
    }
}
echo "Done.";

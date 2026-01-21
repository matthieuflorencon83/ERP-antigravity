<?php
require_once __DIR__ . '/../db.php';

echo "CHECKING SPECIFIC PRO FIELDS...\n";

$fields_to_check = [
    'Sortie Fil', 
    'Oscillo', 
    'Soufflet', 
    'Habillage', 
    'Vantaux'
];

foreach ($fields_to_check as $term) {
    echo "\nSearching for '$term' :\n";
    $stmt = $pdo->prepare("SELECT t.nom, p.label, p.options_liste FROM metrage_points_controle p JOIN metrage_types t ON p.metrage_type_id = t.id WHERE p.label LIKE ? OR p.options_liste LIKE ?");
    $stmt->execute(["%$term%", "%$term%"]);
    $results = $stmt->fetchAll();
    
    if (empty($results)) {
        echo "❌ Not found.\n";
    } else {
        foreach($results as $r) {
            echo "✅ [{$r['nom']}] {$r['label']}\n";
        }
    }
}

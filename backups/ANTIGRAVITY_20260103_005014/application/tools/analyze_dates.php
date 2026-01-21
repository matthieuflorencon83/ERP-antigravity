<?php
require_once __DIR__ . '/../db.php';

echo "ANALYSIS OF DELIVERY DATES DUPLICATION\n";
echo "--------------------------------------\n";

// Count non-nulls
$stmt = $pdo->query("SELECT 
    COUNT(*) as total,
    COUNT(date_livraison_prevue) as has_livraison_prevue,
    COUNT(date_prevue_cible) as has_prevue_cible,
    SUM(CASE WHEN date_livraison_prevue IS NOT NULL AND date_prevue_cible IS NOT NULL THEN 1 ELSE 0 END) as both_set
FROM commandes_achats");
$counts = $stmt->fetch(PDO::FETCH_ASSOC);

print_r($counts);

// Check if they are different when both are set
$stmt = $pdo->query("SELECT id, date_livraison_prevue, date_prevue_cible 
FROM commandes_achats 
WHERE date_livraison_prevue IS NOT NULL 
AND date_prevue_cible IS NOT NULL 
AND date_livraison_prevue != date_prevue_cible");
$diffs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($diffs) > 0) {
    echo "\nWARNING: " . count($diffs) . " rows have DIFFERENT dates:\n";
    foreach($diffs as $d) {
        echo "ID {$d['id']}: Prevue={$d['date_livraison_prevue']} vs Cible={$d['date_prevue_cible']}\n";
    }
} else {
    echo "\nOK: When both are set, they are identical.\n";
}

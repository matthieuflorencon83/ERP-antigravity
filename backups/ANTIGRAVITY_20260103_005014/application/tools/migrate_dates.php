<?php
require_once __DIR__ . '/../db.php';

echo "MIGRATION START\n";

// 1. Copy target date to planned date if planned date is null
$stmt = $pdo->prepare("UPDATE commandes_achats 
SET date_livraison_prevue = date_prevue_cible 
WHERE date_livraison_prevue IS NULL");
$stmt->execute();
echo "Updated " . $stmt->rowCount() . " rows (filled nulls).\n";

// 2. Report verification
$stmt = $pdo->query("SELECT COUNT(*) FROM commandes_achats WHERE date_livraison_prevue IS NULL");
echo "Remaining nulls: " . $stmt->fetchColumn() . "\n";

echo "MIGRATION COMPLETE\n";

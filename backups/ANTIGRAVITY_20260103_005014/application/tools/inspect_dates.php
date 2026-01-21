<?php
require_once __DIR__ . '/../db.php';

echo "CURRENT DATE: " . date('Y-m-d') . "\n";
echo "--------------------------------------\n";

$stmt = $pdo->query("SELECT id, date_livraison_prevue, precision_date FROM commandes_achats ORDER BY date_livraison_prevue ASC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "FOUND " . count($rows) . " ORDERS:\n";
foreach ($rows as $r) {
    echo "ID {$r['id']}: " . ($r['date_livraison_prevue'] ?? 'NULL') . " (Precision: {$r['precision_date']})\n";
}

<?php
require_once __DIR__ . '/../db.php';

echo "SHIFTING DATES TO FUTURE (2026)\n";

try {
    // Determine offset: Move 1 year forward
    $stmt = $pdo->query("UPDATE commandes_achats SET date_livraison_prevue = DATE_ADD(date_livraison_prevue, INTERVAL 1 YEAR)");
    echo "Updated " . $stmt->rowCount() . " rows.\n";
    
    // Check new dates
    $stmt = $pdo->query("SELECT id, date_livraison_prevue FROM commandes_achats ORDER BY date_livraison_prevue ASC LIMIT 5");
    foreach($stmt->fetchAll() as $row) {
        echo "ID {$row['id']} -> {$row['date_livraison_prevue']}\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

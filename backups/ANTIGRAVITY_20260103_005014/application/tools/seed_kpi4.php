<?php
error_reporting(E_ERROR | E_PARSE);
require_once __DIR__ . '/../db.php';

try {
    $sql = "INSERT INTO commandes_achats (affaire_id, fournisseur_id, ref_interne, designation, date_en_attente, date_commande, date_arc_recu, date_livraison_prevue) 
            VALUES (999, 1, 'CMD-LIV-01', 'Gaines ventilation - Livraison J-2', NOW(), NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 2 DAY))";
    $pdo->exec($sql);
    echo "✅ KPI 4 (Livraison) Inserted.\n";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

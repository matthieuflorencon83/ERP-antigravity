<?php
error_reporting(E_ERROR | E_PARSE); // Hide warnings
require_once __DIR__ . '/../db.php';

try {
    echo "🌱 Seeding Dashboard Data (SAFE MODE)...\n";

    // 1. Ensure Affaire exists
    $pdo->exec("INSERT IGNORE INTO affaires (id, nom_affaire, client_id) VALUES (999, 'Chantier Démo Dashboard', 1)");
    
    // 2. Prepare Insert
    $sql = "INSERT INTO commandes_achats (affaire_id, fournisseur_id, ref_interne, designation, date_en_attente, date_commande) 
            VALUES (999, 1, 'CMD-WAIT-01', 'Menuiseries Alu - Devis en attente', NOW(), NULL)";
    
    echo "Executing: $sql\n";
    $pdo->exec($sql);
    echo "✅ KPI 1 Inserted.\n";

    $sql = "INSERT INTO commandes_achats (affaire_id, fournisseur_id, ref_interne, designation, date_en_attente, date_commande, date_arc_recu) 
            VALUES (999, 1, 'CMD-SENT-01', 'Moteurs Volets - Envoyée', NOW(), NOW(), NULL)";
    $pdo->exec($sql);
    echo "✅ KPI 2 Inserted.\n";

    $sql = "INSERT INTO commandes_achats (affaire_id, fournisseur_id, ref_interne, designation, date_en_attente, date_commande, date_arc_recu, date_livraison_reelle) 
            VALUES (999, 1, 'CMD-ARC-01', 'Vitrages - ARC Validé', NOW(), NOW(), NOW(), NULL)";
    $pdo->exec($sql);
    echo "✅ KPI 3 Inserted.\n";

    echo "✅ Success.\n";

} catch (PDOException $e) {
    echo "❌ FATAL SQL ERROR: " . $e->getMessage() . "\n";
}

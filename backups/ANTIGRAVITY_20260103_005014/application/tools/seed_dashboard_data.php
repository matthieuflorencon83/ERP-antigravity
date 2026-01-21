<?php
require_once __DIR__ . '/../db.php';

try {
    echo "🌱 Seeding Dashboard Data...\n";

    // 1. Ensure Suppliers exist
    $pdo->exec("INSERT IGNORE INTO fournisseurs (nom, email) VALUES 
        ('Schüco', 'contact@schuco.com'),
        ('Somfy', 'pro@somfy.fr'),
        ('Saint-Gobain', 'vitrage@sg.com')");
    $f1 = $pdo->lastInsertId() ?: 1; // Fallback to 1 if exists

    // 2. Ensure Affaire exists
    $pdo->exec("INSERT IGNORE INTO affaires (id, nom_affaire, client_id) VALUES (999, 'Chantier Démo Dashboard', 1)");
    
    // 3. Insert Purchase Orders (Commandes Achats)
    // KPI 1: En Attente
    $pdo->exec("INSERT INTO commandes_achats (affaire_id, fournisseur_id, ref_interne, designation, date_en_attente, date_commande) 
                VALUES (999, $f1, 'CMD-WAIT-01', 'Menuiseries Alu - Devis en attente', NOW(), NULL)");

    // KPI 2: Commandée
    $pdo->exec("INSERT INTO commandes_achats (affaire_id, fournisseur_id, ref_interne, designation, date_en_attente, date_commande, date_arc_recu) 
                VALUES (999, $f1, 'CMD-SENT-01', 'Moteurs Volets - Envoyée', NOW(), NOW(), NULL)");

    // KPI 3: ARC Reçu
    $pdo->exec("INSERT INTO commandes_achats (affaire_id, fournisseur_id, ref_interne, designation, date_en_attente, date_commande, date_arc_recu, date_livraison_reelle) 
                VALUES (999, $f1, 'CMD-ARC-01', 'Vitrages - ARC Validé', NOW(), NOW(), NOW(), NULL)");

    // KPI 4: Livraison Prévue
    $pdo->exec("INSERT INTO commandes_achats (affaire_id, fournisseur_id, ref_interne, designation, date_en_attente, date_commande, date_arc_recu, date_livraison_prevue, date_livraison_reelle) 
                VALUES (999, $f1, 'CMD-LIV-01', 'Gaines ventilation - Livraison J-2', NOW(), NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 2 DAY), NULL)");

    echo "✅ Dashboard Data Injected (4 KPIs populated).\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}

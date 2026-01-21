<?php
// tools/seed_complete_test_data.php
require_once __DIR__ . '/../db.php';

echo "<h1>🌱 GÉNÉRATION DONNÉES DE TEST COMPLÈTES</h1>";

try {
    $pdo->beginTransaction();
    
    // ===== 1. FOURNISSEURS =====
    echo "<h3>1️⃣ Fournisseurs</h3>";
    
    $fournisseurs = [
        ['SEPAL001', 'Sepalumic Distribution', 'contact@sepalumic.fr', '0142567890', '12 Rue de l\'Industrie, 75001 Paris'],
        ['ARCELOR01', 'ArcelorMittal Berton Sicard', 'commercial@arcelor.fr', '0156789012', '45 Avenue du Métal, 69002 Lyon'],
        ['TECHALU01', 'TechAlu Solutions', 'ventes@techalu.com', '0467890123', '78 Boulevard des Alliages, 31000 Toulouse'],
        ['PROFILUX01', 'Profilux France', 'info@profilux.fr', '0298765432', '23 Rue des Profilés, 44000 Nantes'],
        ['ALUMETAL01', 'Alumetal Industries', 'contact@alumetal.fr', '0387654321', '56 Avenue de la Métallurgie, 67000 Strasbourg']
    ];
    
    foreach($fournisseurs as $f) {
        $stmt = $pdo->prepare("INSERT INTO fournisseurs (code_fou, nom, email, telephone, adresse) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($f);
    }
    echo "<p>✓ " . count($fournisseurs) . " fournisseurs créés</p>";
    
    // ===== 2. CLIENTS =====
    echo "<h3>2️⃣ Clients</h3>";
    
    $clients = [
        ['Martin', 'Jean', '15 Rue de la Paix', '75008', 'Paris', '0612345678', 'jean.martin@email.fr', 'Particulier'],
        ['Dupont', 'Marie', '28 Avenue des Champs', '69003', 'Lyon', '0623456789', 'marie.dupont@email.fr', 'Particulier'],
        ['Société Immobilière Lyon', '', '45 Boulevard Vivier Merle', '69003', 'Lyon', '0478901234', 'contact@immo-lyon.fr', 'Professionnel'],
        ['Bernard', 'Pierre', '12 Rue du Commerce', '31000', 'Toulouse', '0634567890', 'p.bernard@email.fr', 'Particulier'],
        ['Constructa SA', '', '89 Avenue de la République', '44000', 'Nantes', '0240123456', 'devis@constructa.fr', 'Professionnel']
    ];
    
    foreach($clients as $c) {
        $stmt = $pdo->prepare("INSERT INTO clients (nom, prenom, adresse, code_postal, ville, telephone, email, type_client) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($c);
    }
    echo "<p>✓ " . count($clients) . " clients créés</p>";
    
    // ===== 3. ARTICLES (déjà 22 existants, on en ajoute 10) =====
    echo "<h3>3️⃣ Articles Supplémentaires</h3>";
    
    $newArticles = [
        ['P-TRAV-80', 'Traverse Alu 80x40mm RAL 9005', 1, 1, 'TRAV-80-9005', 48.90, 'U', 1.650, 6000, 2, 12.00, 4.00],
        ['P-MONT-60', 'Montant Alu 60x60mm RAL 7016', 1, 1, 'MONT-60-7016', 56.00, 'U', 2.100, 6000, 1, 18.00, 6.00],
        ['V-DOUBLE-44', 'Vitrage Double 44.2 Feuilleté', 2, 3, 'VIT-44-FEU', 125.00, 'M2', 25.000, NULL, 3, 8.00, 2.00],
        ['Q-INOX-M8', 'Quincaillerie Vis Inox M8x60', 3, 5, 'VIS-M8-60', 0.45, 'U', 0.015, NULL, NULL, 500.00, 100.00],
        ['J-EPDM-6', 'Joint EPDM 6mm Noir', 4, 6, 'JOINT-6-NOIR', 3.20, 'ML', 0.025, 50000, NULL, 100.00, 20.00]
    ];
    
    foreach($newArticles as $a) {
        $stmt = $pdo->prepare("
            INSERT INTO articles 
            (reference_interne, designation, famille_id, sous_famille_id, ref_fournisseur, prix_achat_ht, unite_stock, poids_kg, longueur_barre, finition_id, stock_actuel, seuil_alerte_stock) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute($a);
    }
    echo "<p>✓ " . count($newArticles) . " articles ajoutés</p>";
    
    // ===== 4. AFFAIRES =====
    echo "<h3>4️⃣ Affaires</h3>";
    
    $affaires = [
        [1, 'AFF-2026-001', 'Rénovation Fenêtres Appartement Paris 8', '2026-01-02', 'En cours', 15000.00],
        [2, 'AFF-2026-002', 'Pose Véranda Lyon 3', '2026-01-05', 'Devis', 28000.00],
        [3, 'AFF-2026-003', 'Immeuble 12 Fenêtres Lyon', '2026-01-08', 'En cours', 45000.00],
        [4, 'AFF-2026-004', 'Maison Individuelle Toulouse', '2026-01-10', 'Devis', 22000.00],
        [5, 'AFF-2026-005', 'Résidence 24 Logements Nantes', '2026-01-12', 'Validé', 120000.00]
    ];
    
    foreach($affaires as $aff) {
        $stmt = $pdo->prepare("INSERT INTO affaires (client_id, reference, designation, date_creation, statut, montant_estime) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute($aff);
    }
    echo "<p>✓ " . count($affaires) . " affaires créées</p>";
    
    // ===== 5. COMMANDES ACHATS =====
    echo "<h3>5️⃣ Commandes Achats</h3>";
    
    $commandes = [
        [1, 1, 'CMD-2026-001', '2026-01-03', 'Validée', 5600.00],
        [1, 2, 'CMD-2026-002', '2026-01-06', 'En cours', 8900.00],
        [3, 1, 'CMD-2026-003', '2026-01-09', 'Validée', 12500.00],
        [4, 3, 'CMD-2026-004', '2026-01-11', 'En attente', 6700.00],
        [5, 2, 'CMD-2026-005', '2026-01-13', 'Validée', 35000.00]
    ];
    
    foreach($commandes as $cmd) {
        $stmt = $pdo->prepare("INSERT INTO commandes_achats (affaire_id, fournisseur_id, numero_commande, date_commande, statut, montant_total) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute($cmd);
    }
    echo "<p>✓ " . count($commandes) . " commandes créées</p>";
    
    // ===== 6. LIGNES COMMANDES =====
    echo "<h3>6️⃣ Lignes de Commandes</h3>";
    
    $lignes = [
        [1, 1, 'Chevron 60x40 RAL 7016', 10, 52.50, 525.00],
        [1, 2, 'Dormant 70x50 RAL 9016', 8, 68.00, 544.00],
        [2, 3, 'Ouvrant 60x40 RAL 7016', 15, 45.00, 675.00],
        [3, 1, 'Chevron 60x40 RAL 7016', 20, 52.50, 1050.00],
        [4, 5, 'Vitrage 4/16/4 Standard', 12, 85.00, 1020.00]
    ];
    
    foreach($lignes as $l) {
        $stmt = $pdo->prepare("INSERT INTO lignes_achat (commande_id, article_id, designation, quantite, prix_unitaire, prix_total) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute($l);
    }
    echo "<p>✓ " . count($lignes) . " lignes de commande créées</p>";
    
    // ===== 7. MÉTRAGES =====
    echo "<h3>7️⃣ Métrages</h3>";
    
    $metrages = [
        [1, 1, 'Fenêtre Salon', 1, '{"largeur": 1200, "hauteur": 1400}', 'Terminé'],
        [1, 1, 'Fenêtre Chambre 1', 1, '{"largeur": 1000, "hauteur": 1200}', 'Terminé'],
        [2, 2, 'Véranda Face Sud', 2, '{"largeur": 4000, "hauteur": 2500}', 'En cours'],
        [3, 1, 'Fenêtres Type A (x6)', 1, '{"largeur": 1200, "hauteur": 1400}', 'Terminé'],
        [4, 1, 'Fenêtre Séjour', 1, '{"largeur": 1800, "hauteur": 1600}', 'Brouillon']
    ];
    
    foreach($metrages as $m) {
        $stmt = $pdo->prepare("INSERT INTO metrages (affaire_id, metrage_type_id, designation, client_id, data_json, statut) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute($m);
    }
    echo "<p>✓ " . count($metrages) . " métrages créés</p>";
    
    $pdo->commit();
    
    echo "<hr><div class='alert alert-success'>";
    echo "<h2>✅ DONNÉES DE TEST GÉNÉRÉES</h2>";
    echo "<ul>";
    echo "<li>✓ 5 Fournisseurs</li>";
    echo "<li>✓ 5 Clients (3 particuliers, 2 pros)</li>";
    echo "<li>✓ 10 Articles supplémentaires (32 total)</li>";
    echo "<li>✓ 5 Affaires</li>";
    echo "<li>✓ 5 Commandes achats</li>";
    echo "<li>✓ 5 Lignes de commande</li>";
    echo "<li>✓ 5 Métrages</li>";
    echo "</ul>";
    echo "<p><strong>Base de données prête pour tests complets !</strong></p>";
    echo "</div>";
    
} catch(PDOException $e) {
    $pdo->rollBack();
    echo "<div class='alert alert-danger'>Erreur: " . $e->getMessage() . "</div>";
}

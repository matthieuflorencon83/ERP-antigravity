<?php
// tools/update_test_data_with_statuses.php
require_once __DIR__ . '/../db.php';

echo "<h1>📊 Mise à jour données test - Tous statuts</h1>";

try {
    $pdo->beginTransaction();
    
    // Update existing commandes with different statuses
    echo "<h3>Mise à jour commandes existantes</h3>";
    
    // Commande 1: EN ATTENTE (seulement date_creation)
    $pdo->exec("UPDATE commandes_achats SET 
        date_commande = NULL,
        date_arc_recu = NULL,
        date_livraison_prevue = NULL,
        date_livraison_reelle = NULL,
        designation = 'Profilés alu - En attente validation'
        WHERE id = 1");
    echo "<p>✓ Commande 1: EN ATTENTE</p>";
    
    // Commande 2: COMMANDÉE (date_commande renseignée)
    $pdo->exec("UPDATE commandes_achats SET 
        date_commande = '2026-01-02',
        date_arc_recu = NULL,
        date_livraison_prevue = NULL,
        date_livraison_reelle = NULL,
        designation = 'Vitrages - Commandé aujourd\'hui'
        WHERE id = 2");
    echo "<p>✓ Commande 2: COMMANDÉE</p>";
    
    // Commande 3: ARC REÇU (date_arc_recu renseignée)
    $pdo->exec("UPDATE commandes_achats SET 
        date_commande = '2025-12-28',
        date_arc_recu = '2025-12-30',
        date_livraison_prevue = '2026-01-10',
        date_livraison_reelle = NULL,
        designation = 'Quincaillerie - ARC reçu'
        WHERE id = 3");
    echo "<p>✓ Commande 3: ARC REÇU</p>";
    
    // Commande 4: LIVRAISON PRÉVUE (date_livraison_prevue renseignée)
    $pdo->exec("UPDATE commandes_achats SET 
        date_commande = '2025-12-20',
        date_arc_recu = '2025-12-22',
        date_livraison_prevue = '2026-01-05',
        date_livraison_reelle = NULL,
        designation = 'Joints - Livraison dans 3 jours'
        WHERE id = 4");
    echo "<p>✓ Commande 4: LIVRAISON PRÉVUE</p>";
    
    // Commande 5: LIVRÉE (date_livraison_reelle renseignée)
    $pdo->exec("UPDATE commandes_achats SET 
        date_commande = '2025-12-15',
        date_arc_recu = '2025-12-17',
        date_livraison_prevue = '2025-12-28',
        date_livraison_reelle = '2025-12-29',
        designation = 'Accessoires - Déjà livrée'
        WHERE id = 5");
    echo "<p>✓ Commande 5: LIVRÉE (ne s'affiche pas)</p>";
    
    // Add 2 more EN ATTENTE
    $stmt = $pdo->prepare("INSERT INTO commandes_achats (affaire_id, fournisseur_id, numero_commande, date_creation, designation, montant_total) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([2, 1, 'CMD-2026-006', '2026-01-01', 'Profilés supplémentaires - En attente', 3500.00]);
    $stmt->execute([3, 2, 'CMD-2026-007', '2026-01-02', 'Vitrage feuilleté - En attente', 8900.00]);
    echo "<p>✓ 2 commandes EN ATTENTE ajoutées</p>";
    
    // Add 1 more COMMANDÉE
    $stmt = $pdo->prepare("INSERT INTO commandes_achats (affaire_id, fournisseur_id, numero_commande, date_creation, date_commande, designation, montant_total) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([4, 3, 'CMD-2026-008', '2025-12-30', '2026-01-01', 'Quincaillerie pose - Commandé', 1200.00]);
    echo "<p>✓ 1 commande COMMANDÉE ajoutée</p>";
    
    $pdo->commit();
    
    echo "<hr><div class='alert alert-success'>";
    echo "<h2>✅ DONNÉES MISES À JOUR</h2>";
    echo "<ul>";
    echo "<li>3 commandes EN ATTENTE (IDs 1, 6, 7)</li>";
    echo "<li>2 commandes COMMANDÉES (IDs 2, 8)</li>";
    echo "<li>1 commande ARC REÇU (ID 3)</li>";
    echo "<li>1 commande LIVRAISON PRÉVUE (ID 4)</li>";
    echo "<li>1 commande LIVRÉE (ID 5 - ne s'affiche pas)</li>";
    echo "</ul>";
    echo "<p><strong>Rafraîchissez le dashboard pour voir toutes les tuiles remplies !</strong></p>";
    echo "</div>";
    
} catch(PDOException $e) {
    $pdo->rollBack();
    echo "<div class='alert alert-danger'>Erreur: " . $e->getMessage() . "</div>";
}

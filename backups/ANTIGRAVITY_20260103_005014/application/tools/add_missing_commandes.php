<?php
require_once __DIR__ . '/../db.php';

echo "<h2>Ajout commandes manquantes</h2>";

try {
    $pdo->beginTransaction();
    
    // Check current count
    $count = $pdo->query("SELECT COUNT(*) FROM commandes_achats")->fetchColumn();
    echo "<p>Commandes actuelles: $count</p>";
    
    // Add missing commandes
    $stmt = $pdo->prepare("
        INSERT INTO commandes_achats 
        (affaire_id, fournisseur_id, numero_commande, ref_interne, date_creation, date_commande, date_arc_recu, date_livraison_prevue, designation, montant_total, statut) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Commande 6: EN ATTENTE
    $stmt->execute([2, 1, 'CMD-2026-006', 'REF-006', '2026-01-01', NULL, NULL, NULL, 'Profilés supplémentaires - En attente', 3500.00, 'En attente']);
    echo "<p>✓ Commande 6 créée (EN ATTENTE)</p>";
    
    // Commande 7: EN ATTENTE
    $stmt->execute([3, 2, 'CMD-2026-007', 'REF-007', '2026-01-02', NULL, NULL, NULL, 'Vitrage feuilleté - En attente', 8900.00, 'En attente']);
    echo "<p>✓ Commande 7 créée (EN ATTENTE)</p>";
    
    // Commande 8: COMMANDÉE
    $stmt->execute([4, 3, 'CMD-2026-008', 'REF-008', '2025-12-30', '2026-01-01', NULL, NULL, 'Quincaillerie pose - Commandé', 1200.00, 'Commandée']);
    echo "<p>✓ Commande 8 créée (COMMANDÉE)</p>";
    
    $pdo->commit();
    
    $newCount = $pdo->query("SELECT COUNT(*) FROM commandes_achats")->fetchColumn();
    echo "<div class='alert alert-success'>";
    echo "<h4>✅ Commandes créées</h4>";
    echo "<p>Total: $newCount commandes</p>";
    echo "<p>Rafraîchissez le dashboard !</p>";
    echo "</div>";
    
} catch(PDOException $e) {
    $pdo->rollBack();
    echo "<div class='alert alert-danger'>Erreur: " . $e->getMessage() . "</div>";
}

<?php
require_once __DIR__ . '/../db.php';

echo "<h2>🔍 Diagnostic Commandes Orphelines</h2>";

// Check commandes without valid affaire
$stmt = $pdo->query("
    SELECT ca.id, ca.ref_interne, ca.affaire_id, ca.fournisseur_id, ca.designation
    FROM commandes_achats ca
    LEFT JOIN affaires a ON ca.affaire_id = a.id
    LEFT JOIN fournisseurs f ON ca.fournisseur_id = f.id
    WHERE a.id IS NULL OR f.id IS NULL
");
$orphans = $stmt->fetchAll();

echo "<h4>Commandes orphelines trouvées: " . count($orphans) . "</h4>";

if(count($orphans) > 0) {
    echo "<table class='table table-sm table-danger'>";
    echo "<tr><th>ID</th><th>Ref</th><th>Affaire ID</th><th>Fournisseur ID</th><th>Désignation</th></tr>";
    foreach($orphans as $o) {
        echo "<tr>";
        echo "<td>{$o['id']}</td>";
        echo "<td>{$o['ref_interne']}</td>";
        echo "<td>" . ($o['affaire_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($o['fournisseur_id'] ?? 'NULL') . "</td>";
        echo "<td>{$o['designation']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h4>🔧 Correction automatique</h4>";
    
    $pdo->beginTransaction();
    
    // Fix: assign to first valid affaire and fournisseur
    $firstAffaire = $pdo->query("SELECT id FROM affaires ORDER BY id LIMIT 1")->fetchColumn();
    $firstFournisseur = $pdo->query("SELECT id FROM fournisseurs ORDER BY id LIMIT 1")->fetchColumn();
    
    foreach($orphans as $o) {
        $updates = [];
        $params = [];
        
        if($o['affaire_id'] === null || $pdo->query("SELECT id FROM affaires WHERE id = {$o['affaire_id']}")->fetchColumn() === false) {
            $updates[] = "affaire_id = ?";
            $params[] = $firstAffaire;
        }
        
        if($o['fournisseur_id'] === null || $pdo->query("SELECT id FROM fournisseurs WHERE id = {$o['fournisseur_id']}")->fetchColumn() === false) {
            $updates[] = "fournisseur_id = ?";
            $params[] = $firstFournisseur;
        }
        
        if(count($updates) > 0) {
            $params[] = $o['id'];
            $sql = "UPDATE commandes_achats SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo "<p>✓ Commande {$o['id']} corrigée</p>";
        }
    }
    
    $pdo->commit();
    
    echo "<div class='alert alert-success'>";
    echo "<h4>✅ Correction terminée</h4>";
    echo "<p>Toutes les commandes ont maintenant des affaires et fournisseurs valides.</p>";
    echo "<p><strong>Rafraîchissez le dashboard !</strong></p>";
    echo "</div>";
} else {
    echo "<p class='text-success'>✓ Aucune commande orpheline</p>";
}

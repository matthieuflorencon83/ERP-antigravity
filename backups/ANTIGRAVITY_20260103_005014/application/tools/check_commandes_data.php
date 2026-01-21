<?php
require_once __DIR__ . '/../db.php';

echo "<h2>Vérification données commandes</h2>";

$stmt = $pdo->query("
    SELECT ca.id, ca.ref_interne, ca.affaire_id, ca.fournisseur_id, 
           f.nom as fournisseur_nom, a.nom_affaire
    FROM commandes_achats ca
    LEFT JOIN fournisseurs f ON ca.fournisseur_id = f.id
    LEFT JOIN affaires a ON ca.affaire_id = a.id
    WHERE ca.date_commande IS NULL
    ORDER BY ca.id
");

$commandes = $stmt->fetchAll();

echo "<table class='table table-sm'>";
echo "<tr><th>ID</th><th>Ref</th><th>Affaire ID</th><th>Affaire Nom</th><th>Fournisseur ID</th><th>Fournisseur Nom</th></tr>";
foreach($commandes as $c) {
    $affaireStatus = $c['nom_affaire'] ? '✓' : '❌';
    $fournisseurStatus = $c['fournisseur_nom'] ? '✓' : '❌';
    
    echo "<tr>";
    echo "<td>{$c['id']}</td>";
    echo "<td>{$c['ref_interne']}</td>";
    echo "<td>{$c['affaire_id']}</td>";
    echo "<td>$affaireStatus {$c['nom_affaire']}</td>";
    echo "<td>{$c['fournisseur_id']}</td>";
    echo "<td>$fournisseurStatus {$c['fournisseur_nom']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p>Total EN ATTENTE: " . count($commandes) . "</p>";

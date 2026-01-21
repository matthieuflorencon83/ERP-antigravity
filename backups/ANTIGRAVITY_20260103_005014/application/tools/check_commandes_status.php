<?php
require_once __DIR__ . '/../db.php';

echo "<h2>Vérification Commandes</h2>";

$stmt = $pdo->query("
    SELECT ca.id, ca.ref_interne, ca.affaire_id, a.nom_affaire, ca.date_creation, ca.date_commande, ca.date_arc_recu, ca.designation
    FROM commandes_achats ca
    LEFT JOIN affaires a ON ca.affaire_id = a.id
    ORDER BY ca.id
");
$commandes = $stmt->fetchAll();

echo "<table class='table table-sm'>";
echo "<tr><th>ID</th><th>Ref</th><th>Affaire</th><th>Création</th><th>Commande</th><th>ARC</th><th>Statut</th></tr>";
foreach($commandes as $c) {
    $statut = 'LIVRÉE';
    if($c['date_commande'] === null) $statut = 'EN ATTENTE';
    elseif($c['date_arc_recu'] === null) $statut = 'COMMANDÉE';
    elseif($c['date_livraison_prevue'] !== null) $statut = 'LIVRAISON PRÉVUE';
    
    $affaireOk = $c['nom_affaire'] ? '✓' : '❌ ORPHELINE';
    
    echo "<tr>";
    echo "<td>{$c['id']}</td>";
    echo "<td>{$c['ref_interne']}</td>";
    echo "<td>{$affaireOk} {$c['nom_affaire']}</td>";
    echo "<td>{$c['date_creation']}</td>";
    echo "<td>{$c['date_commande']}</td>";
    echo "<td>{$c['date_arc_recu']}</td>";
    echo "<td><strong>$statut</strong></td>";
    echo "</tr>";
}
echo "</table>";

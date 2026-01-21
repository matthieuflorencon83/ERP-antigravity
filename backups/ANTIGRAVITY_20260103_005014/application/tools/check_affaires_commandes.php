<?php
// tools/check_affaires_commandes.php
require_once __DIR__ . '/../db.php';

echo "<h2>🔍 Vérification Affaires et Commandes</h2>";

// Check affaires
echo "<h4>Affaires existantes</h4>";
$stmt = $pdo->query("SELECT id, reference, designation FROM affaires ORDER BY id");
$affaires = $stmt->fetchAll();

echo "<table class='table table-sm'>";
echo "<tr><th>ID</th><th>Référence</th><th>Désignation</th></tr>";
foreach($affaires as $a) {
    echo "<tr><td>{$a['id']}</td><td>{$a['reference']}</td><td>{$a['designation']}</td></tr>";
}
echo "</table>";

// Check commandes
echo "<h4>Commandes existantes</h4>";
$stmt = $pdo->query("SELECT id, numero_commande, affaire_id, fournisseur_id FROM commandes_achats ORDER BY id");
$commandes = $stmt->fetchAll();

echo "<table class='table table-sm'>";
echo "<tr><th>ID</th><th>N° Commande</th><th>Affaire ID</th><th>Fournisseur ID</th></tr>";
foreach($commandes as $c) {
    echo "<tr><td>{$c['id']}</td><td>{$c['numero_commande']}</td><td>{$c['affaire_id']}</td><td>{$c['fournisseur_id']}</td></tr>";
}
echo "</table>";

// Check orphaned commandes
echo "<h4>Commandes sans affaire valide</h4>";
$stmt = $pdo->query("
    SELECT c.id, c.numero_commande, c.affaire_id 
    FROM commandes_achats c 
    LEFT JOIN affaires a ON c.affaire_id = a.id 
    WHERE c.affaire_id IS NOT NULL AND a.id IS NULL
");
$orphans = $stmt->fetchAll();

if(count($orphans) > 0) {
    echo "<table class='table table-sm table-danger'>";
    echo "<tr><th>Commande ID</th><th>N° Commande</th><th>Affaire ID (invalide)</th></tr>";
    foreach($orphans as $o) {
        echo "<tr><td>{$o['id']}</td><td>{$o['numero_commande']}</td><td>{$o['affaire_id']}</td></tr>";
    }
    echo "</table>";
    
    echo "<div class='alert alert-warning'>";
    echo "<p>Ces commandes pointent vers des affaires inexistantes.</p>";
    echo "<p><strong>Solution:</strong> Mettre affaire_id à NULL ou créer les affaires manquantes.</p>";
    echo "</div>";
} else {
    echo "<p class='text-success'>✓ Toutes les commandes ont des affaires valides</p>";
}

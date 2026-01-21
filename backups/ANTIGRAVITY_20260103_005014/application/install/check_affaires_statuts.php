<?php
// Vérifier les statuts d'affaires existants
require_once '../db.php';

echo "<h2>Statuts des affaires dans la BDD</h2>";
echo "<pre>";

$stmt = $pdo->query("
    SELECT statut, COUNT(*) as nb
    FROM affaires
    GROUP BY statut
    ORDER BY nb DESC
");

echo "Statut                    Nombre\n";
echo "----------------------------------------\n";
while ($row = $stmt->fetch()) {
    echo sprintf("%-25s %d\n", $row['statut'], $row['nb']);
}

echo "</pre>";

echo "<h2>Exemples d'affaires</h2>";
echo "<pre>";
$stmt = $pdo->query("SELECT id, nom_affaire, statut FROM affaires LIMIT 10");
while ($row = $stmt->fetch()) {
    echo sprintf("ID: %d | %s | Statut: %s\n", $row['id'], $row['nom_affaire'], $row['statut']);
}
echo "</pre>";
?>

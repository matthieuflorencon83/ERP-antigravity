<?php
// Test structure table utilisateurs
require_once '../db.php';

echo "<h2>Structure table utilisateurs</h2>";
echo "<pre>";

$stmt = $pdo->query("DESCRIBE utilisateurs");
while ($row = $stmt->fetch()) {
    echo sprintf("%-30s %-20s\n", $row['Field'], $row['Type']);
}

echo "</pre>";

echo "<h2>Exemple de données</h2>";
echo "<pre>";
$stmt = $pdo->query("SELECT * FROM utilisateurs LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($user);
echo "</pre>";
?>

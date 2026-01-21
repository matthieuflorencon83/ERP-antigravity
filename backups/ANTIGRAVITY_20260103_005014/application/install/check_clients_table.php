<?php
// Vérifier structure table clients
require_once '../db.php';

echo "<h2>Structure table clients</h2>";
echo "<pre>";

$stmt = $pdo->query("DESCRIBE clients");
while ($row = $stmt->fetch()) {
    echo sprintf("%-30s %-20s\n", $row['Field'], $row['Type']);
}

echo "</pre>";
?>

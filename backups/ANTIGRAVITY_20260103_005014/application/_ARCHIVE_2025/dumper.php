<?php
require_once 'db.php';
$stmt = $pdo->query("DESCRIBE commandes_achats");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Columns: " . implode(", ", $columns);
?>

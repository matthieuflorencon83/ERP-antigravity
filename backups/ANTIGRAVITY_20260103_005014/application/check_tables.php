<?php
require 'db.php';
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "TABLES DISPONIBLES :\n";
print_r($tables);
?>

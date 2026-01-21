<?php
require_once __DIR__ . '/../db.php';
$stmt = $pdo->query("DESCRIBE commandes_achats");
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo $col['Field'] . "\n";
}

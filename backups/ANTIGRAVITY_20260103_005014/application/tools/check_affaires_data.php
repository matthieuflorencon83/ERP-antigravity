<?php
require_once __DIR__ . '/../db.php';

echo "CHECKING AFFAIRES DATA\n";
$stmt = $pdo->query("SELECT id, nom_affaire, designation FROM affaires LIMIT 5");
foreach($stmt->fetchAll() as $row) {
    echo "ID {$row['id']} | {$row['nom_affaire']} | {$row['designation']}\n";
}

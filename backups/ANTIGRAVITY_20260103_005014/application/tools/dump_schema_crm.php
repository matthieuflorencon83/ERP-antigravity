<?php
require_once '../db.php';
$tables = ['fournisseurs', 'fournisseur_contacts'];
foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $stmt = $pdo->query("DESCRIBE $t");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo " - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    echo "\n";
}

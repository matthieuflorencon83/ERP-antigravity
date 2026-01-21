<?php
require 'db.php';

echo "=== Structure de la table 'task_items' ===\n";
$stmt = $pdo->query("DESCRIBE task_items");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n=== Exemple de données ===\n";
$stmt = $pdo->query("SELECT * FROM task_items LIMIT 3");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
?>

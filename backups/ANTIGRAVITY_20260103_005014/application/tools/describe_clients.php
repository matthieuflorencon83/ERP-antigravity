<?php
require_once __DIR__ . '/../db.php';
$stmt = $pdo->query("DESCRIBE clients");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $col) {
    echo $col['Field'] . " | " . $col['Type'] . "\n";
}

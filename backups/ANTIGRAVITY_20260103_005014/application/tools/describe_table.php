<?php
require_once __DIR__ . '/../db.php';
$stmt = $pdo->query("DESCRIBE metrage_types");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $col) {
    echo $col['Field'] . " | " . $col['Type'] . " | " . $col['Null'] . " | " . $col['Default'] . "\n";
}

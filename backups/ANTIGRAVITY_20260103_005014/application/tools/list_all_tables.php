<?php
require_once __DIR__ . '/../db.php';
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<h1>Full Table List</h1><ul>";
foreach($tables as $t) {
    echo "<li>$t</li>";
}
echo "</ul>";

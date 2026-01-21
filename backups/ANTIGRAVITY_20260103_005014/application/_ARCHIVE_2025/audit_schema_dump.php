<?php
require_once 'db.php';
header('Content-Type: text/plain');

$tables = [];
$stmt = $pdo->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    echo "--- TABLE: $table ---\n";
    try {
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        echo $res['Create Table'] . "\n\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n\n";
    }
}
?>

<?php
require_once 'db.php';
try {
    $stmt = $pdo->query("DESCRIBE articles");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "COLUMNS:\n";
    foreach($rows as $r) {
        echo $r['Field'] . " (" . $r['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

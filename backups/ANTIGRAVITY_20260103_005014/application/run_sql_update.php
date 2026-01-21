<?php
require_once 'db.php';

try {
    $sql = file_get_contents('update_technical_rules.sql');
    $pdo->exec($sql);
    echo "SQL Rules Injected Successfully ✅";
} catch (PDOException $e) {
    echo "SQL Error: " . $e->getMessage();
}

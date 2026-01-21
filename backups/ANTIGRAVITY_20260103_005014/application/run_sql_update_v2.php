<?php
require_once 'db.php';

try {
    $sql = file_get_contents('update_technical_rules_v2.sql');
    $pdo->exec($sql);
    echo "SQL Rules V2 Injected Successfully ✅ (Column type modified)";
} catch (PDOException $e) {
    echo "SQL Error: " . $e->getMessage();
}

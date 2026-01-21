<?php
require 'db.php';

$tables_to_check = ['mysql', 'antigravity'];
$found = [];

foreach ($tables_to_check as $target) {
    // Check if table exists in current DB
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$target]);
    if ($stmt->fetch()) {
        $found[] = $target;
    }
}

if (count($found) > 0) {
    echo "Found tables in database 'antigravity': " . implode(', ', $found);
} else {
    echo "No potential confusion tables found in 'antigravity' database.";
}
?>

<?php
require_once 'db.php';
try {
    echo "Checking metrage_types table...\n";
    $stm = $pdo->query("SELECT COUNT(*) FROM metrage_types");
    $count = $stm->fetchColumn();
    echo "Count: $count\n";
    
    $desc = $pdo->query("DESCRIBE metrage_types")->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns:\n";
    foreach($desc as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

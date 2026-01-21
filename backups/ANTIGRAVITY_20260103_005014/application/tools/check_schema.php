<?php
require 'db.php';
try {
    $stmt = $pdo->query("DESCRIBE parametres_generaux");
    echo "COLUMNS FOUND:\n";
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        echo "- " . $col['Field'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>

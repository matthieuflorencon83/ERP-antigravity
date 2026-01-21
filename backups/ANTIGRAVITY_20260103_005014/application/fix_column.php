<?php
require_once 'db.php';
try {
    $pdo->exec("ALTER TABLE metrage_points_controle MODIFY type_saisie VARCHAR(100)");
    echo "Column resized to VARCHAR(100).\n";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

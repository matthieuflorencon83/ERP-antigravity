<?php
require_once '../db.php';
try {
    echo "Adding image_url column...\n";
    $pdo->exec("ALTER TABLE metrage_points_controle ADD COLUMN image_url VARCHAR(255) NULL AFTER message_aide");
    echo "Column added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>

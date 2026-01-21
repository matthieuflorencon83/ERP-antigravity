<?php
require_once __DIR__ . '/../db.php';

try {
    echo "Updating dashboard_postits table...<br>";
    
    // Add columns if they don't exist
    $cols = [
        'x_pos' => "INT DEFAULT 20",
        'y_pos' => "INT DEFAULT 20",
        'width' => "INT DEFAULT 250",
        'height' => "INT DEFAULT 250"
    ];

    foreach ($cols as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE dashboard_postits ADD COLUMN $col $def");
            echo "Added column $col.<br>";
        } catch (PDOException $e) {
            echo "Column $col already exists or error: " . $e->getMessage() . "<br>";
        }
    }

    echo "Update complete.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

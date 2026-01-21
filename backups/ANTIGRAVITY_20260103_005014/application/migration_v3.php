<?php
require_once 'db.php';

function safe_alter($pdo, $sql) {
    try {
        $pdo->exec($sql);
        echo "OK: $sql<br>";
    } catch (Exception $e) {
        // Ignore "Duplicate column" errors
        if (strpos($e->getMessage(), "Duplicate column") !== false) {
             echo "Info: Column already exists.<br>";
        } else {
             echo "Error: " . $e->getMessage() . "<br>";
        }
    }
}

echo "<h1>Migration V3</h1>";

// 1. Clients (Fast Track)
safe_alter($pdo, "ALTER TABLE clients ADD COLUMN is_temp TINYINT(1) DEFAULT 0");
safe_alter($pdo, "ALTER TABLE clients ADD COLUMN gps_lat FLOAT DEFAULT NULL");
safe_alter($pdo, "ALTER TABLE clients ADD COLUMN gps_lng FLOAT DEFAULT NULL");
safe_alter($pdo, "ALTER TABLE clients ADD COLUMN photo_facade VARCHAR(255) DEFAULT NULL");

// 2. Metrage Points (Règles Expert)
// validation_rules stockera du JSON ex: {"min":900, "alert":"Danger!"}
safe_alter($pdo, "ALTER TABLE metrage_points_controle ADD COLUMN validation_rules TEXT DEFAULT NULL AFTER image_url");

echo "<h2>Done.</h2>";
?>

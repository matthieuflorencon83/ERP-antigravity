<?php
require_once __DIR__ . '/../db.php';

echo "ADDING DESIGNATION COLUMN TO AFFAIRES\n";

try {
    $pdo->exec("ALTER TABLE affaires ADD COLUMN designation VARCHAR(255) AFTER nom_affaire");
    echo "✅ Column 'designation' added successfully.\n";
    
    // Update existing records to have a default value
    $pdo->exec("UPDATE affaires SET designation = CONCAT('Désignation pour ', nom_affaire) WHERE designation IS NULL");
    echo "✅ Existing records updated.\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "⚠️ Column already exists.\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

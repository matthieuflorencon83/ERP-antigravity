<?php
// tools/update_users_schema.php
require_once __DIR__ . '/../db.php';

try {
    echo "Updating Schema for Users...\n";
    
    // Add couleur_plan
    try {
        $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN couleur_plan VARCHAR(7) DEFAULT '#3788d8' AFTER role");
        echo "✅ Column 'couleur_plan' added.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column") !== false) echo "⚠️ Column 'couleur_plan' already exists.\n";
        else echo "❌ Error couleur_plan: " . $e->getMessage() . "\n";
    }

    // Add email
    try {
        $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN email VARCHAR(255) DEFAULT NULL AFTER nom_complet");
        echo "✅ Column 'email' added.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column") !== false) echo "⚠️ Column 'email' already exists.\n";
        else echo "❌ Error email: " . $e->getMessage() . "\n";
    }

} catch (PDOException $e) {
    echo "Critical Error: " . $e->getMessage() . "\n";
}

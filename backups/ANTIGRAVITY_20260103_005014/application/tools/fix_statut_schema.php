<?php
// tools/fix_statut_schema.php
require_once __DIR__ . '/../db.php';

try {
    echo "Fixing table commandes_express...\n";
    
    // 1. Fix 'statut' - Change to VARCHAR to support 'EN_ATTENTE'
    echo "Modifying 'statut' column to VARCHAR(50)...\n";
    $pdo->exec("ALTER TABLE commandes_express MODIFY COLUMN statut VARCHAR(50) DEFAULT 'EN_ATTENTE'");
    
    // 2. Fix 'type_module' - Make it nullable to avoid 'Field doesn't have a default value' error
    // because we are writing to 'module_type' instead.
    echo "Modifying 'type_module' column to be nullable...\n";
    $pdo->exec("ALTER TABLE commandes_express MODIFY COLUMN type_module VARCHAR(50) NULL");

    // 3. Fix 'details_json' - Ensure it can hold enough data
    // It is already JSON or TEXT, ensuring consistency
    echo "Ensuring 'details_json' is flexible...\n";
    $pdo->exec("ALTER TABLE commandes_express MODIFY COLUMN details_json LONGTEXT");

    echo "Schema fixed successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

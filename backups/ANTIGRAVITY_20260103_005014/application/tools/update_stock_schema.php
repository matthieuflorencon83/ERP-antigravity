<?php
// tools/update_stock_schema.php
require_once __DIR__ . '/../db.php';

try {
    echo "Updating Schema for Stock-Affaire Link...\n";
    
    // 1. Add column affaire_id to stocks_mouvements
    $pdo->exec("
        ALTER TABLE stocks_mouvements 
        ADD COLUMN affaire_id INT DEFAULT NULL AFTER user_id,
        ADD CONSTRAINT fk_mvt_affaire FOREIGN KEY (affaire_id) REFERENCES affaires(id) ON DELETE SET NULL
    ");
    
    echo "✅ Column 'affaire_id' added to 'stocks_mouvements'.\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "⚠️ Column 'affaire_id' already exists.\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

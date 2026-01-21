<?php
require_once __DIR__ . '/../db.php';

echo "HARDENING TRACEABILITY: Adding metrage_ligne_id to lignes_achat...\n";

try {
    // 1. Add Column if missing
    $cols = $pdo->query("SHOW COLUMNS FROM lignes_achat LIKE 'metrage_ligne_id'")->fetch();
    if (!$cols) {
        $pdo->exec("ALTER TABLE lignes_achat ADD COLUMN metrage_ligne_id INT NULL AFTER commande_id");
        echo "✅ Column 'metrage_ligne_id' added.\n";
        
        // Add FK
        try {
            $pdo->exec("ALTER TABLE lignes_achat ADD CONSTRAINT fk_lignes_achat_metrage FOREIGN KEY (metrage_ligne_id) REFERENCES metrage_lignes(id) ON DELETE SET NULL");
             echo "✅ Foreign Key 'fk_lignes_achat_metrage' added.\n";
        } catch (Exception $e) {
            echo "⚠️ FK error (maybe exists): " . $e->getMessage() . "\n";
        }
    } else {
        echo "ℹ️ Column 'metrage_ligne_id' already exists.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

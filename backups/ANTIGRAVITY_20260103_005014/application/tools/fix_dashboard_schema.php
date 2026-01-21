<?php
require_once __DIR__ . '/../db.php';

function addCol($pdo, $table, $col, $def) {
    try {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
        echo "✅ Added $table.$col\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ $table.$col already exists.\n";
        } else {
            echo "❌ Error adding $table.$col: " . $e->getMessage() . "\n";
        }
    }
}

try {
    echo "🔧 Repairing Schema for Dashboard...\n";
    addCol($pdo, 'commandes_achats', 'designation', 'VARCHAR(255) NULL');
    echo "🏁 Schema repaired.\n";
} catch (Exception $e) {
    echo "❌ FATAL: " . $e->getMessage();
}

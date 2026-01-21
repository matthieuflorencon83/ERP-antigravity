<?php
require_once __DIR__ . '/../db.php';

echo "DROPPING REDUNDANT COLUMN\n";
try {
    $pdo->exec("ALTER TABLE commandes_achats DROP COLUMN date_prevue_cible");
    echo "✅ Column date_prevue_cible dropped successfully.\n";
} catch (PDOException $e) {
    echo "⚠️ Error (maybe already dropped): " . $e->getMessage() . "\n";
}

<?php
require_once __DIR__ . '/../db.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM metrage_lignes LIKE 'statut_traitement'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE metrage_lignes ADD COLUMN statut_traitement ENUM('NON_TRAITE', 'PARTIEL', 'TRAITE') DEFAULT 'NON_TRAITE'");
        echo "✅ Column 'statut_traitement' added to metrage_lignes.\n";
    } else {
        echo "ℹ️ Column 'statut_traitement' already exists.\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

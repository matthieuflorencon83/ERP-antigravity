<?php
require_once __DIR__ . '/../db.php';

try {
    echo "Adding chemin_pdf_arc column...\n";
    $pdo->exec("ALTER TABLE commandes_achats ADD COLUMN chemin_pdf_arc VARCHAR(255) NULL AFTER chemin_pdf_bdc");
    echo "OK.\n";
} catch (PDOException $e) {
    echo "Error (maybe already exists): " . $e->getMessage() . "\n";
}

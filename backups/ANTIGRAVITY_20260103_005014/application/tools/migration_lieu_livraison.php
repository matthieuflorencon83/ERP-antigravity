<?php
require_once __DIR__ . '/../db.php';

try {
    echo "Migration Table COMMANDES_ACHATS...\n";
    echo "Changing lieu_livraison from ENUM to VARCHAR(255)...\n";
    
    $pdo->exec("ALTER TABLE commandes_achats MODIFY COLUMN lieu_livraison VARCHAR(255) DEFAULT 'Atelier (Arts Alu)'");
    
    echo "Migration terminée.\n";

} catch (Exception $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
}

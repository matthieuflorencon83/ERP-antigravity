<?php
/**
 * Script d'installation des tables Métrage
 * Exécute le schéma SQL pour créer toutes les tables nécessaires
 */

require_once '../db.php';

echo "=== INSTALLATION MODULE MÉTRAGE ===\n\n";

try {
    // Lire le fichier SQL
    $sql = file_get_contents(__DIR__ . '/metrage_schema.sql');
    
    if ($sql === false) {
        throw new Exception("Impossible de lire le fichier metrage_schema.sql");
    }
    
    // Exécuter le SQL
    $pdo->exec($sql);
    
    echo "✅ Tables métrage créées avec succès !\n\n";
    
    // Vérifier les tables créées
    $stmt = $pdo->query("SHOW TABLES LIKE 'metrage%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables créées :\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    echo "\n✅ Installation terminée !\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n";
    exit(1);
}
?>

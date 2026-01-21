<?php
// run_migration.php - Exécute la migration pour affaire_id NULL
require_once 'db.php';

try {
    echo "Migration: Permettre affaire_id NULL...\n\n";
    
    // Modifier la colonne
    $pdo->exec("ALTER TABLE `metrage_interventions` MODIFY COLUMN `affaire_id` INT NULL");
    echo "✓ Colonne modifiée avec succès\n\n";
    
    // Vérifier
    $result = $pdo->query("SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'metrage_interventions' 
        AND COLUMN_NAME = 'affaire_id'")->fetch(PDO::FETCH_ASSOC);
    
    echo "Vérification:\n";
    echo "Colonne: {$result['COLUMN_NAME']}\n";
    echo "Type: {$result['COLUMN_TYPE']}\n";
    echo "NULL autorisé: {$result['IS_NULLABLE']}\n\n";
    
    if ($result['IS_NULLABLE'] === 'YES') {
        echo "✅ Migration réussie ! Les métrages libres sont maintenant possibles.\n";
    } else {
        echo "❌ Erreur: La colonne n'accepte toujours pas NULL\n";
    }
    
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

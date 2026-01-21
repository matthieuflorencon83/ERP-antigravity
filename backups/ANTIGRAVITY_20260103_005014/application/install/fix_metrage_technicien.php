<?php
/**
 * Fix Métrage - Ajouter colonne technicien_id
 */

require_once '../db.php';

echo "=== FIX MÉTRAGE - TECHNICIEN_ID ===\n\n";

try {
    // Vérifier si la colonne existe déjà
    $stmt = $pdo->query("DESCRIBE metrage_interventions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('technicien_id', $columns)) {
        echo "✅ La colonne technicien_id existe déjà !\n";
    } else {
        echo "⚙️ Ajout de la colonne technicien_id...\n";
        
        $pdo->exec("
            ALTER TABLE `metrage_interventions` 
            ADD COLUMN `technicien_id` INT DEFAULT NULL AFTER `statut`,
            ADD FOREIGN KEY (`technicien_id`) REFERENCES `utilisateurs`(`id`) ON DELETE SET NULL
        ");
        
        echo "✅ Colonne technicien_id ajoutée avec succès !\n";
    }
    
    // Vérifier la structure finale
    echo "\n📊 Structure finale de metrage_interventions :\n";
    $stmt = $pdo->query("DESCRIBE metrage_interventions");
    while ($row = $stmt->fetch()) {
        echo sprintf("  %-20s %-20s %s\n", $row['Field'], $row['Type'], $row['Key']);
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n";
    exit(1);
}
?>

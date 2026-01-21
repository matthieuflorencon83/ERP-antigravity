<?php
/**
 * Script d'exécution du schéma Client CRM
 */

require_once 'db.php';

echo "Exécution du script de mise à jour Client CRM...\n\n";

try {
    // Lire le fichier SQL
    $sql = file_get_contents('update_clients_schema.sql');
    
    // Séparer les requêtes (par point-virgule)
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    $success = 0;
    $errors = 0;
    
    foreach ($queries as $query) {
        // Ignorer les commentaires et lignes vides
        if (empty($query) || strpos($query, '--') === 0 || strpos($query, '/*') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($query);
            $success++;
            echo "✅ Requête exécutée avec succès\n";
        } catch (PDOException $e) {
            // Ignorer les erreurs "already exists" ou "duplicate column"
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "⚠️  Élément déjà existant (ignoré)\n";
            } else {
                $errors++;
                echo "❌ Erreur: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n=================================\n";
    echo "✅ Script terminé !\n";
    echo "Requêtes réussies: $success\n";
    echo "Erreurs: $errors\n";
    echo "=================================\n\n";
    
    // Vérification des tables créées
    echo "Vérification des tables...\n\n";
    
    $tables = ['clients', 'client_contacts', 'client_adresses', 'client_telephones', 'client_emails'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' existe\n";
            
            // Compter les colonnes
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll();
            echo "   → " . count($columns) . " colonnes\n";
        } else {
            echo "❌ Table '$table' MANQUANTE\n";
        }
    }
    
    echo "\n✅ Migration terminée avec succès !\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR FATALE: " . $e->getMessage() . "\n";
    exit(1);
}

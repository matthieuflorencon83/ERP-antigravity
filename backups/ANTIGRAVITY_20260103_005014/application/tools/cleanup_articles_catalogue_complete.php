<?php
// tools/cleanup_articles_catalogue_complete.php
require_once __DIR__ . '/../db.php';

echo "<h2>🔍 Nettoyage Complet articles_catalogue</h2>";

try {
    // Find ALL FKs referencing articles_catalogue
    echo "<h4>Étape 1: Détection Foreign Keys</h4>";
    $stmt = $pdo->query("
        SELECT 
            TABLE_NAME, 
            CONSTRAINT_NAME, 
            COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'antigravity' 
        AND REFERENCED_TABLE_NAME = 'articles_catalogue'
    ");
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table class='table table-sm'>";
    echo "<tr><th>Table</th><th>Contrainte</th><th>Colonne</th></tr>";
    foreach($fks as $fk) {
        echo "<tr><td>{$fk['TABLE_NAME']}</td><td>{$fk['CONSTRAINT_NAME']}</td><td>{$fk['COLUMN_NAME']}</td></tr>";
    }
    echo "</table>";
    
    // Drop all FKs
    echo "<h4>Étape 2: Suppression Foreign Keys</h4>";
    foreach($fks as $fk) {
        try {
            $pdo->exec("ALTER TABLE `{$fk['TABLE_NAME']}` DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`");
            echo "<p>✓ FK supprimée: <code>{$fk['CONSTRAINT_NAME']}</code> sur <code>{$fk['TABLE_NAME']}</code></p>";
        } catch(PDOException $e) {
            echo "<p>⚠️ Erreur: " . $e->getMessage() . "</p>";
        }
    }
    
    // Rename columns and add new FKs to articles
    echo "<h4>Étape 3: Migration vers 'articles'</h4>";
    foreach($fks as $fk) {
        $table = $fk['TABLE_NAME'];
        $oldCol = $fk['COLUMN_NAME'];
        
        // Check if column needs renaming
        if(stripos($oldCol, 'catalogue') !== false) {
            $newCol = str_replace('_catalogue', '', $oldCol);
            $newCol = str_replace('catalogue_', '', $newCol);
            
            try {
                $pdo->exec("ALTER TABLE `$table` CHANGE `$oldCol` `$newCol` INT");
                echo "<p>✓ Colonne renommée: <code>$table.$oldCol</code> → <code>$newCol</code></p>";
                
                // Add FK to articles
                $pdo->exec("ALTER TABLE `$table` ADD FOREIGN KEY (`$newCol`) REFERENCES `articles`(id)");
                echo "<p>✓ FK ajoutée: <code>$table.$newCol</code> → <code>articles.id</code></p>";
            } catch(PDOException $e) {
                echo "<p>⚠️ {$e->getMessage()}</p>";
            }
        }
    }
    
    // Drop table
    echo "<h4>Étape 4: Suppression Table</h4>";
    $pdo->exec("DROP TABLE IF EXISTS articles_catalogue");
    echo "<p>✓ Table <code>articles_catalogue</code> supprimée</p>";
    
    echo "<hr><div class='alert alert-success'>";
    echo "<h4>✅ NETTOYAGE COMPLET TERMINÉ</h4>";
    echo "<p>" . count($fks) . " Foreign Keys migrées vers <code>articles</code></p>";
    echo "<p>Table <code>articles_catalogue</code> supprimée définitivement</p>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>";
    echo "<p>Erreur : " . $e->getMessage() . "</p>";
    echo "</div>";
}

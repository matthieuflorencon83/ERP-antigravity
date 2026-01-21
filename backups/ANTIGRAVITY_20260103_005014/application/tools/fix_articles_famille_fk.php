<?php
// tools/fix_articles_famille_fk.php
require_once __DIR__ . '/../db.php';

echo "<h3>🔧 Correction FK articles.famille_id</h3>";

try {
    // Check current FK
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'antigravity' 
        AND TABLE_NAME = 'articles'
        AND COLUMN_NAME = 'famille_id'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $currentFK = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($currentFK) {
        echo "<p>FK actuelle: <code>{$currentFK['CONSTRAINT_NAME']}</code> → <code>{$currentFK['REFERENCED_TABLE_NAME']}</code></p>";
        
        if($currentFK['REFERENCED_TABLE_NAME'] == 'familles_articles') {
            echo "<p class='text-success'>✓ FK déjà correcte</p>";
        } else {
            // Drop old FK
            $pdo->exec("ALTER TABLE articles DROP FOREIGN KEY `{$currentFK['CONSTRAINT_NAME']}`");
            echo "<p>✓ Ancienne FK supprimée</p>";
            
            // Add new FK
            $pdo->exec("ALTER TABLE articles ADD CONSTRAINT fk_articles_famille FOREIGN KEY (famille_id) REFERENCES familles_articles(id)");
            echo "<p>✓ Nouvelle FK ajoutée: <code>articles.famille_id</code> → <code>familles_articles.id</code></p>";
        }
    } else {
        // No FK exists, add it
        $pdo->exec("ALTER TABLE articles ADD CONSTRAINT fk_articles_famille FOREIGN KEY (famille_id) REFERENCES familles_articles(id)");
        echo "<p>✓ FK ajoutée: <code>articles.famille_id</code> → <code>familles_articles.id</code></p>";
    }
    
    echo "<div class='alert alert-success'>✅ FK articles.famille_id correctement configurée</div>";
    
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur: " . $e->getMessage() . "</div>";
}

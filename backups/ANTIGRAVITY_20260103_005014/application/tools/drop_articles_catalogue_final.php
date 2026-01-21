<?php
// tools/drop_articles_catalogue_final.php
require_once __DIR__ . '/../db.php';

echo "<h2>🗑️ Suppression articles_catalogue</h2>";

try {
    // Check if exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'articles_catalogue'");
    $exists = $stmt->fetch();
    
    if($exists) {
        $count = $pdo->query("SELECT COUNT(*) FROM articles_catalogue")->fetchColumn();
        echo "<p>Table trouvée : <strong>$count lignes</strong></p>";
        
        // Drop it
        $pdo->exec("DROP TABLE IF EXISTS articles_catalogue");
        
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Table Supprimée</h4>";
        echo "<p><code>articles_catalogue</code> a été supprimée avec succès.</p>";
        echo "</div>";
        
        // Verify
        $stmt = $pdo->query("SHOW TABLES LIKE 'articles%'");
        $remaining = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h4>Tables Articles Restantes:</h4>";
        echo "<ul>";
        foreach($remaining as $t) {
            echo "<li>✓ $t</li>";
        }
        echo "</ul>";
        
    } else {
        echo "<div class='alert alert-info'>";
        echo "<p>Table <code>articles_catalogue</code> déjà supprimée.</p>";
        echo "</div>";
    }
    
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>";
    echo "<p>Erreur : " . $e->getMessage() . "</p>";
    echo "</div>";
}

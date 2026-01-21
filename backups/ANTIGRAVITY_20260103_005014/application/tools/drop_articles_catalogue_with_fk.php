<?php
// tools/drop_articles_catalogue_with_fk.php
require_once __DIR__ . '/../db.php';

echo "<h2>🔧 Suppression articles_catalogue (avec FK)</h2>";

try {
    // Step 1: Drop FK constraint
    echo "<h4>Étape 1: Suppression Foreign Key</h4>";
    $pdo->exec("ALTER TABLE besoins_chantier DROP FOREIGN KEY besoins_chantier_ibfk_4");
    echo "<p>✓ FK <code>besoins_chantier_ibfk_4</code> supprimée</p>";
    
    // Step 2: Check if column should be updated
    echo "<h4>Étape 2: Mise à jour colonne</h4>";
    $stmt = $pdo->query("DESCRIBE besoins_chantier");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if(in_array('article_catalogue_id', $cols)) {
        // Rename to article_id if not exists
        if(!in_array('article_id', $cols)) {
            $pdo->exec("ALTER TABLE besoins_chantier CHANGE article_catalogue_id article_id INT");
            echo "<p>✓ Colonne renommée: <code>article_catalogue_id</code> → <code>article_id</code></p>";
            
            // Add new FK to articles
            $pdo->exec("ALTER TABLE besoins_chantier ADD FOREIGN KEY (article_id) REFERENCES articles(id)");
            echo "<p>✓ Nouvelle FK ajoutée: <code>besoins_chantier.article_id</code> → <code>articles.id</code></p>";
        }
    }
    
    // Step 3: Drop table
    echo "<h4>Étape 3: Suppression Table</h4>";
    $pdo->exec("DROP TABLE IF EXISTS articles_catalogue");
    echo "<p>✓ Table <code>articles_catalogue</code> supprimée</p>";
    
    echo "<hr><div class='alert alert-success'>";
    echo "<h4>✅ NETTOYAGE TERMINÉ</h4>";
    echo "<p>La table <code>articles_catalogue</code> et ses dépendances ont été supprimées.</p>";
    echo "<p>Toutes les références pointent maintenant vers <code>articles</code>.</p>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>";
    echo "<p>Erreur : " . $e->getMessage() . "</p>";
    echo "</div>";
}

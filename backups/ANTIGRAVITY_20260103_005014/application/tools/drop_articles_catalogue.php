<?php
// tools/drop_articles_catalogue.php
require_once __DIR__ . '/../db.php';

echo "<h3>Suppression de la table articles_catalogue</h3>";

try {
    // Final verification
    $count = $pdo->query("SELECT COUNT(*) FROM articles_catalogue")->fetchColumn();
    echo "<p>Lignes dans articles_catalogue: <strong>$count</strong></p>";
    
    if($count > 0) {
        echo "<div class='alert alert-warning'>⚠️ La table contient encore $count ligne(s). Vérifiez avant de supprimer.</div>";
        echo "<form method='POST'>";
        echo "<button type='submit' name='confirm' class='btn btn-danger'>Confirmer la suppression</button>";
        echo "</form>";
        
        if(isset($_POST['confirm'])) {
            $pdo->exec("DROP TABLE articles_catalogue");
            echo "<div class='alert alert-success'>✅ Table 'articles_catalogue' supprimée avec succès</div>";
        }
    } else {
        $pdo->exec("DROP TABLE articles_catalogue");
        echo "<div class='alert alert-success'>✅ Table 'articles_catalogue' supprimée avec succès</div>";
    }
    
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur: " . $e->getMessage() . "</div>";
}

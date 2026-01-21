<?php
// tools/action3_finalize_catalog_migration.php
require_once __DIR__ . '/../db.php';

echo "<h2>✅ ACTION 3: MIGRATION CATALOGUE FINALE</h2>";

try {
    $pdo->beginTransaction();
    
    // Step 1: Drop duplicate familles table
    echo "<h4>Étape 1: Suppression table 'familles' (doublon)</h4>";
    $pdo->exec("DROP TABLE IF EXISTS familles");
    echo "✓ Table 'familles' supprimée<br>";
    
    // Step 2: Add FK columns if not exist
    echo "<h4>Étape 2: Ajout colonnes FK</h4>";
    try {
        $pdo->exec("ALTER TABLE articles ADD COLUMN famille_id INT NULL AFTER fournisseur_prefere_id");
        echo "✓ Colonne 'famille_id' ajoutée<br>";
    } catch(PDOException $e) {
        echo "○ 'famille_id' existe déjà<br>";
    }
    
    try {
        $pdo->exec("ALTER TABLE articles ADD COLUMN sous_famille_id INT NULL AFTER famille_id");
        echo "✓ Colonne 'sous_famille_id' ajoutée<br>";
    } catch(PDOException $e) {
        echo "○ 'sous_famille_id' existe déjà<br>";
    }
    
    // Step 3: Migrate text data to IDs
    echo "<h4>Étape 3: Migration données texte → ID</h4>";
    
    $stmt = $pdo->exec("
        UPDATE articles a
        JOIN familles_articles f ON UPPER(TRIM(a.famille)) = UPPER(TRIM(f.designation))
        SET a.famille_id = f.id
        WHERE a.famille IS NOT NULL AND a.famille != ''
    ");
    echo "✓ $stmt articles.famille_id mis à jour<br>";
    
    $stmt = $pdo->exec("
        UPDATE articles a
        JOIN sous_familles_articles sf ON UPPER(TRIM(a.sous_famille)) = UPPER(TRIM(sf.designation))
        SET a.sous_famille_id = sf.id
        WHERE a.sous_famille IS NOT NULL AND a.sous_famille != ''
    ");
    echo "✓ $stmt articles.sous_famille_id mis à jour<br>";
    
    // Step 4: Add FK constraints
    echo "<h4>Étape 4: Ajout contraintes FK</h4>";
    try {
        $pdo->exec("ALTER TABLE articles ADD CONSTRAINT fk_articles_famille FOREIGN KEY (famille_id) REFERENCES familles_articles(id)");
        echo "✓ FK famille_id → familles_articles<br>";
    } catch(PDOException $e) {
        echo "○ FK famille_id déjà existante<br>";
    }
    
    try {
        $pdo->exec("ALTER TABLE articles ADD CONSTRAINT fk_articles_sous_famille FOREIGN KEY (sous_famille_id) REFERENCES sous_familles_articles(id)");
        echo "✓ FK sous_famille_id → sous_familles_articles<br>";
    } catch(PDOException $e) {
        echo "○ FK sous_famille_id déjà existante<br>";
    }
    
    // Step 5: Drop old text columns
    echo "<h4>Étape 5: Suppression colonnes texte obsolètes</h4>";
    try {
        $pdo->exec("ALTER TABLE articles DROP COLUMN famille");
        echo "✓ Colonne 'famille' (texte) supprimée<br>";
    } catch(PDOException $e) {
        echo "○ Colonne 'famille' déjà supprimée<br>";
    }
    
    try {
        $pdo->exec("ALTER TABLE articles DROP COLUMN sous_famille");
        echo "✓ Colonne 'sous_famille' (texte) supprimée<br>";
    } catch(PDOException $e) {
        echo "○ Colonne 'sous_famille' déjà supprimée<br>";
    }
    
    $pdo->commit();
    
    echo "<hr><div class='alert alert-success'>";
    echo "<h4>✅ ACTION 3 TERMINÉE</h4>";
    echo "<p>Le schéma catalogue est maintenant optimisé avec Foreign Keys propres.</p>";
    echo "</div>";
    
    // Verification
    echo "<h4>Vérification Finale</h4>";
    $nullCount = $pdo->query("SELECT COUNT(*) FROM articles WHERE famille_id IS NULL")->fetchColumn();
    if($nullCount > 0) {
        echo "⚠️ $nullCount articles sans famille_id<br>";
    } else {
        echo "✓ Tous les articles ont une famille_id<br>";
    }
    
} catch(PDOException $e) {
    $pdo->rollBack();
    echo "<div class='alert alert-danger'>❌ Erreur: " . $e->getMessage() . "</div>";
}

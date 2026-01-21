<?php
// tools/execute_catalog_optimization.php
require_once __DIR__ . '/../db.php';

echo "<h3>Migration: Optimisation Schéma Catalogue</h3>";

try {
    $pdo->beginTransaction();
    
    // ÉTAPE 1: Supprimer table 'familles' (doublon)
    echo "<h4>Étape 1: Suppression table 'familles'</h4>";
    $pdo->exec("DROP TABLE IF EXISTS familles");
    echo "✓ Table 'familles' supprimée<br>";
    
    // ÉTAPE 2: Ajouter colonnes FK
    echo "<h4>Étape 2: Ajout colonnes FK</h4>";
    try {
        $pdo->exec("ALTER TABLE articles ADD COLUMN famille_id INT NULL AFTER famille");
        echo "✓ Colonne 'famille_id' ajoutée<br>";
    } catch(PDOException $e) {
        if(strpos($e->getMessage(), 'Duplicate') === false) throw $e;
        echo "○ 'famille_id' existe déjà<br>";
    }
    
    try {
        $pdo->exec("ALTER TABLE articles ADD COLUMN sous_famille_id INT NULL AFTER sous_famille");
        echo "✓ Colonne 'sous_famille_id' ajoutée<br>";
    } catch(PDOException $e) {
        if(strpos($e->getMessage(), 'Duplicate') === false) throw $e;
        echo "○ 'sous_famille_id' existe déjà<br>";
    }
    
    // ÉTAPE 3: Migrer données texte → ID
    echo "<h4>Étape 3: Migration données texte → ID</h4>";
    
    // Famille
    $stmt = $pdo->exec("
        UPDATE articles a
        JOIN familles_articles f ON UPPER(TRIM(a.famille)) = UPPER(TRIM(f.designation))
        SET a.famille_id = f.id
        WHERE a.famille IS NOT NULL AND a.famille != ''
    ");
    echo "✓ $stmt articles.famille_id mis à jour<br>";
    
    // Sous-Famille
    $stmt = $pdo->exec("
        UPDATE articles a
        JOIN sous_familles_articles sf ON UPPER(TRIM(a.sous_famille)) = UPPER(TRIM(sf.designation))
        SET a.sous_famille_id = sf.id
        WHERE a.sous_famille IS NOT NULL AND a.sous_famille != ''
    ");
    echo "✓ $stmt articles.sous_famille_id mis à jour<br>";
    
    // ÉTAPE 4: Ajouter contraintes FK
    echo "<h4>Étape 4: Ajout contraintes FK</h4>";
    try {
        $pdo->exec("ALTER TABLE articles ADD FOREIGN KEY (famille_id) REFERENCES familles_articles(id)");
        echo "✓ FK famille_id → familles_articles<br>";
    } catch(PDOException $e) {
        echo "○ FK famille_id déjà existante<br>";
    }
    
    try {
        $pdo->exec("ALTER TABLE articles ADD FOREIGN KEY (sous_famille_id) REFERENCES sous_familles_articles(id)");
        echo "✓ FK sous_famille_id → sous_familles_articles<br>";
    } catch(PDOException $e) {
        echo "○ FK sous_famille_id déjà existante<br>";
    }
    
    // ÉTAPE 5: Supprimer colonnes texte obsolètes
    echo "<h4>Étape 5: Suppression colonnes texte</h4>";
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
    
    // ÉTAPE 6: Fabricants - Décision
    echo "<h4>Étape 6: Table 'fabricants'</h4>";
    $countFabricants = $pdo->query("SELECT COUNT(*) FROM fabricants")->fetchColumn();
    $countLinked = $pdo->query("SELECT COUNT(*) FROM articles WHERE fabricant_id IS NOT NULL")->fetchColumn();
    
    if($countLinked == 0) {
        echo "⚠️ Table 'fabricants' non utilisée ($countFabricants lignes, 0 articles liés)<br>";
        echo "→ Conservée pour usage futur<br>";
    } else {
        echo "✓ Table 'fabricants' utilisée ($countLinked articles liés)<br>";
    }
    
    $pdo->commit();
    
    echo "<hr><div class='alert alert-success'><h4>✅ Migration Terminée</h4>";
    echo "<p>Le schéma catalogue est maintenant optimisé avec des Foreign Keys propres.</p></div>";
    
    // Vérification finale
    echo "<h4>Vérification Finale</h4>";
    $stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE famille_id IS NULL");
    $nullFamille = $stmt->fetchColumn();
    if($nullFamille > 0) {
        echo "⚠️ $nullFamille articles sans famille_id (vérifier données)<br>";
    } else {
        echo "✓ Tous les articles ont une famille_id<br>";
    }
    
} catch(PDOException $e) {
    $pdo->rollBack();
    echo "<div class='alert alert-danger'>Erreur: " . $e->getMessage() . "</div>";
}

<?php
// tools/migrate_couleur_to_finition_fk.php
require_once __DIR__ . '/../db.php';

echo "<h2>🎨 Migration Couleur → Finition FK</h2>";

try {
    $pdo->beginTransaction();
    
    // Step 1: Check current state
    echo "<h4>Étape 1: État Actuel</h4>";
    $stmt = $pdo->query("DESCRIBE articles");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasCouleurRal = in_array('couleur_ral', $cols);
    $hasFinitionId = in_array('finition_id', $cols);
    
    echo "<p>couleur_ral (VARCHAR): " . ($hasCouleurRal ? "✓ Existe" : "✗ Absente") . "</p>";
    echo "<p>finition_id (INT FK): " . ($hasFinitionId ? "✓ Existe" : "✗ Absente") . "</p>";
    
    // Step 2: Add finition_id if not exists
    if(!$hasFinitionId) {
        echo "<h4>Étape 2: Ajout colonne finition_id</h4>";
        $pdo->exec("ALTER TABLE articles ADD COLUMN finition_id INT NULL AFTER couleur_ral");
        echo "<p>✓ Colonne finition_id ajoutée</p>";
    }
    
    // Step 3: Migrate data couleur_ral → finition_id
    echo "<h4>Étape 3: Migration Données</h4>";
    
    if($hasCouleurRal) {
        // Get articles with couleur_ral
        $stmt = $pdo->query("
            SELECT id, couleur_ral 
            FROM articles 
            WHERE couleur_ral IS NOT NULL AND couleur_ral != ''
        ");
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $migrated = 0;
        $notFound = 0;
        
        foreach($articles as $art) {
            // Find matching finition
            $stmt = $pdo->prepare("SELECT id FROM finitions WHERE code_ral = ?");
            $stmt->execute([$art['couleur_ral']]);
            $finitionId = $stmt->fetchColumn();
            
            if($finitionId) {
                $pdo->prepare("UPDATE articles SET finition_id = ? WHERE id = ?")->execute([$finitionId, $art['id']]);
                $migrated++;
            } else {
                $notFound++;
                echo "<p class='text-warning'>⚠️ RAL {$art['couleur_ral']} non trouvé dans finitions (article {$art['id']})</p>";
            }
        }
        
        echo "<p>✓ $migrated articles migrés</p>";
        if($notFound > 0) {
            echo "<p>⚠️ $notFound articles avec RAL non trouvé (finition_id = NULL)</p>";
        }
    }
    
    // Step 4: Add FK constraint
    echo "<h4>Étape 4: Ajout Foreign Key</h4>";
    try {
        $pdo->exec("ALTER TABLE articles ADD CONSTRAINT fk_articles_finition FOREIGN KEY (finition_id) REFERENCES finitions(id) ON DELETE SET NULL");
        echo "<p>✓ FK ajoutée: articles.finition_id → finitions.id</p>";
    } catch(PDOException $e) {
        if(stripos($e->getMessage(), 'Duplicate') !== false) {
            echo "<p>○ FK déjà existante</p>";
        } else {
            throw $e;
        }
    }
    
    // Step 5: Drop couleur_ral column
    if($hasCouleurRal) {
        echo "<h4>Étape 5: Suppression couleur_ral (VARCHAR)</h4>";
        $pdo->exec("ALTER TABLE articles DROP COLUMN couleur_ral");
        echo "<p>✓ Colonne couleur_ral supprimée</p>";
    }
    
    $pdo->commit();
    
    echo "<hr><div class='alert alert-success'>";
    echo "<h4>✅ MIGRATION TERMINÉE</h4>";
    echo "<p>Architecture corrigée:</p>";
    echo "<ul>";
    echo "<li>✓ <code>articles.couleur_ral</code> (VARCHAR) → SUPPRIMÉE</li>";
    echo "<li>✓ <code>articles.finition_id</code> (INT FK) → AJOUTÉE</li>";
    echo "<li>✓ Relation: <code>articles.finition_id</code> → <code>finitions.id</code></li>";
    echo "</ul>";
    echo "</div>";
    
    // Verification
    echo "<h4>Vérification</h4>";
    $stmt = $pdo->query("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN finition_id IS NOT NULL THEN 1 ELSE 0 END) as with_finition
        FROM articles
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Articles total: {$stats['total']}</p>";
    echo "<p>Articles avec finition: {$stats['with_finition']}</p>";
    echo "<p>Articles sans finition: " . ($stats['total'] - $stats['with_finition']) . "</p>";
    
} catch(PDOException $e) {
    $pdo->rollBack();
    echo "<div class='alert alert-danger'>";
    echo "<p>Erreur: " . $e->getMessage() . "</p>";
    echo "</div>";
}

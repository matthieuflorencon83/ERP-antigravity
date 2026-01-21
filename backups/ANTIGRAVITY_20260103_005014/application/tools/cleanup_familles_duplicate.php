<?php
// tools/cleanup_familles_duplicate.php
require_once __DIR__ . '/../db.php';

echo "<h2>🔍 Nettoyage Doublon FAMILLES</h2>";

try {
    // Check both tables
    echo "<h4>État Actuel</h4>";
    echo "<table class='table table-sm'>";
    echo "<tr><th>Table</th><th>Lignes</th><th>Colonnes</th><th>Statut</th></tr>";
    
    $tables = ['familles', 'familles_articles'];
    $tableInfo = [];
    
    foreach($tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            $stmt = $pdo->query("DESCRIBE `$table`");
            $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $tableInfo[$table] = ['count' => $count, 'cols' => $cols];
            
            $status = ($table == 'familles') ? "⚠️ DOUBLON" : "✓ ACTIVE";
            echo "<tr><td><strong>$table</strong></td><td>$count</td><td>" . count($cols) . "</td><td>$status</td></tr>";
        } catch(PDOException $e) {
            echo "<tr><td>$table</td><td colspan='3'>N'existe pas</td></tr>";
        }
    }
    echo "</table>";
    
    // Check if familles has FKs
    echo "<h4>Détection Foreign Keys</h4>";
    $stmt = $pdo->query("
        SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'antigravity' 
        AND REFERENCED_TABLE_NAME = 'familles'
    ");
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(count($fks) > 0) {
        echo "<table class='table table-sm'>";
        echo "<tr><th>Table</th><th>Contrainte</th><th>Colonne</th></tr>";
        foreach($fks as $fk) {
            echo "<tr><td>{$fk['TABLE_NAME']}</td><td>{$fk['CONSTRAINT_NAME']}</td><td>{$fk['COLUMN_NAME']}</td></tr>";
        }
        echo "</table>";
        
        // Drop FKs
        echo "<h4>Suppression Foreign Keys</h4>";
        foreach($fks as $fk) {
            $pdo->exec("ALTER TABLE `{$fk['TABLE_NAME']}` DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`");
            echo "<p>✓ FK supprimée: <code>{$fk['CONSTRAINT_NAME']}</code></p>";
        }
    } else {
        echo "<p class='text-success'>✓ Aucune Foreign Key sur 'familles'</p>";
    }
    
    // Drop familles table
    echo "<h4>Suppression Table Doublon</h4>";
    $pdo->exec("DROP TABLE IF EXISTS familles");
    echo "<p>✓ Table <code>familles</code> supprimée</p>";
    
    echo "<hr><div class='alert alert-success'>";
    echo "<h4>✅ NETTOYAGE TERMINÉ</h4>";
    echo "<p>Table <code>familles</code> (doublon) supprimée</p>";
    echo "<p>Table <code>familles_articles</code> reste active</p>";
    echo "</div>";
    
    // Verify articles.famille_id points to familles_articles
    echo "<h4>Vérification Intégrité</h4>";
    $stmt = $pdo->query("
        SELECT REFERENCED_TABLE_NAME 
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'antigravity' 
        AND TABLE_NAME = 'articles'
        AND COLUMN_NAME = 'famille_id'
    ");
    $ref = $stmt->fetchColumn();
    
    if($ref == 'familles_articles') {
        echo "<p class='text-success'>✓ <code>articles.famille_id</code> → <code>familles_articles</code> (OK)</p>";
    } else {
        echo "<p class='text-warning'>⚠️ FK articles.famille_id à vérifier</p>";
    }
    
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>";
    echo "<p>Erreur : " . $e->getMessage() . "</p>";
    echo "</div>";
}

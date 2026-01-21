<?php
// tools/list_orphaned_tables.php
require_once __DIR__ . '/../db.php';

echo "<h3>👻 TABLES ORPHELINES - ANALYSE DÉTAILLÉE</h3>";

$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$phpFiles = glob(__DIR__ . '/../*.php');
$phpFiles = array_merge($phpFiles, glob(__DIR__ . '/../controllers/*.php'));
$phpFiles = array_merge($phpFiles, glob(__DIR__ . '/../ajax/*.php'));
$phpFiles = array_merge($phpFiles, glob(__DIR__ . '/../views/**/*.php'));

echo "<table class='table table-striped table-sm'>";
echo "<thead><tr><th>Table</th><th>Lignes</th><th>Taille</th><th>Statut</th><th>Recommandation</th></tr></thead><tbody>";

$orphanCount = 0;
$totalOrphanedRows = 0;

foreach($tables as $table) {
    $found = false;
    
    foreach($phpFiles as $file) {
        $content = file_get_contents($file);
        // Check for actual SQL usage (not just comments)
        if(preg_match("/FROM\s+`?$table`?|INTO\s+`?$table`?|UPDATE\s+`?$table`?|TABLE\s+`?$table`?/i", $content)) {
            $found = true;
            break;
        }
    }
    
    if(!$found) {
        $orphanCount++;
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        $totalOrphanedRows += $count;
        
        // Get table size
        $stmt = $pdo->query("
            SELECT 
                ROUND(((data_length + index_length) / 1024), 2) AS size_kb
            FROM information_schema.TABLES 
            WHERE table_schema = 'antigravity' 
            AND table_name = '$table'
        ");
        $size = $stmt->fetchColumn();
        
        $status = $count > 0 ? "⚠️ DONNÉES" : "👻 VIDE";
        $recommendation = $count > 0 
            ? "<span class='text-danger'>Analyser avant suppression</span>" 
            : "<span class='text-success'>Supprimer</span>";
        
        echo "<tr class='table-warning'>";
        echo "<td><strong>$table</strong></td>";
        echo "<td>$count</td>";
        echo "<td>{$size} KB</td>";
        echo "<td>$status</td>";
        echo "<td>$recommendation</td>";
        echo "</tr>";
    }
}

echo "</tbody></table>";

echo "<div class='alert alert-info'>";
echo "<strong>Résumé:</strong> $orphanCount tables orphelines détectées<br>";
echo "<strong>Données orphelines:</strong> $totalOrphanedRows lignes au total<br>";
echo "</div>";

// Generate cleanup script
echo "<h4>🗑️ Script de Nettoyage (À VÉRIFIER AVANT EXÉCUTION)</h4>";
echo "<textarea style='width:100%; height:200px; font-family:monospace;'>";

$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach($tables as $table) {
    $found = false;
    foreach($phpFiles as $file) {
        $content = file_get_contents($file);
        if(preg_match("/FROM\s+`?$table`?|INTO\s+`?$table`?|UPDATE\s+`?$table`?|TABLE\s+`?$table`?/i", $content)) {
            $found = true;
            break;
        }
    }
    
    if(!$found) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        if($count == 0) {
            echo "DROP TABLE IF EXISTS `$table`; -- VIDE\n";
        } else {
            echo "-- DROP TABLE IF EXISTS `$table`; -- ATTENTION: $count lignes\n";
        }
    }
}

echo "</textarea>";

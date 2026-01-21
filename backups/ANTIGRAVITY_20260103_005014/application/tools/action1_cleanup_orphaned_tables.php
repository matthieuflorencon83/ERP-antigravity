<?php
// tools/action1_cleanup_orphaned_tables.php
require_once __DIR__ . '/../db.php';

echo "<h2>🗑️ ACTION 1: NETTOYAGE TABLES ORPHELINES</h2>";

$phpFiles = glob(__DIR__ . '/../*.php');
$phpFiles = array_merge($phpFiles, glob(__DIR__ . '/../controllers/*.php'));
$phpFiles = array_merge($phpFiles, glob(__DIR__ . '/../ajax/*.php'));
$phpFiles = array_merge($phpFiles, glob(__DIR__ . '/../views/**/*.php'));

$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$orphanedTables = [];
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
        $orphanedTables[] = ['name' => $table, 'rows' => $count];
    }
}

echo "<h4>📋 Tables Orphelines Détectées: " . count($orphanedTables) . "</h4>";
echo "<table class='table table-sm'>";
echo "<tr><th>Table</th><th>Lignes</th><th>Action</th><th>Résultat</th></tr>";

$dropped = 0;
$skipped = 0;

foreach($orphanedTables as $t) {
    $action = "";
    $result = "";
    
    if($t['rows'] == 0) {
        // Safe to drop
        try {
            $pdo->exec("DROP TABLE IF EXISTS `{$t['name']}`");
            $action = "✓ SUPPRIMÉE";
            $result = "<span class='text-success'>Nettoyée</span>";
            $dropped++;
        } catch(PDOException $e) {
            $action = "❌ ERREUR";
            $result = "<span class='text-danger'>" . $e->getMessage() . "</span>";
        }
    } else {
        // Has data - skip for manual review
        $action = "⚠️ CONSERVÉE";
        $result = "<span class='text-warning'>Données présentes - Revue manuelle requise</span>";
        $skipped++;
    }
    
    $rowClass = $t['rows'] == 0 ? 'table-success' : 'table-warning';
    echo "<tr class='$rowClass'><td>{$t['name']}</td><td>{$t['rows']}</td><td>$action</td><td>$result</td></tr>";
}

echo "</table>";

echo "<div class='alert alert-success'>";
echo "<h4>✅ ACTION 1 TERMINÉE</h4>";
echo "<p><strong>Supprimées:</strong> $dropped tables vides</p>";
echo "<p><strong>Conservées:</strong> $skipped tables avec données (à analyser manuellement)</p>";
echo "</div>";

// List remaining orphaned tables with data
if($skipped > 0) {
    echo "<div class='alert alert-info'>";
    echo "<h5>📊 Tables Orphelines avec Données</h5>";
    echo "<p>Ces tables contiennent des données mais ne sont pas utilisées dans le code. Vérifiez si elles sont nécessaires:</p>";
    echo "<ul>";
    foreach($orphanedTables as $t) {
        if($t['rows'] > 0) {
            echo "<li><strong>{$t['name']}</strong> ({$t['rows']} lignes)</li>";
        }
    }
    echo "</ul>";
    echo "</div>";
}

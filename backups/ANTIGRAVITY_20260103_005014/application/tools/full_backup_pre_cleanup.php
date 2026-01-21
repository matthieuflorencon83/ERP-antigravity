<?php
// tools/full_backup_pre_cleanup.php
require_once __DIR__ . '/../db.php';

$backupDir = __DIR__ . '/../backups';
if(!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$timestamp = date('Y-m-d_H-i-s');
$backupFile = "$backupDir/full_backup_pre_cleanup_$timestamp.sql";

echo "<h2>🛡️ BACKUP COMPLET - PROTOCOLE ZERO-DAY</h2>";
echo "<p><em>Sauvegarde avant nettoyage Ghost Hunter</em></p>";

$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$sql = "-- ANTIGRAVITY FULL BACKUP\n";
$sql .= "-- Date: $timestamp\n";
$sql .= "-- Purpose: Pre-cleanup safety backup\n";
$sql .= "-- Tables: " . count($tables) . "\n\n";

$totalRows = 0;
$totalSize = 0;

echo "<table class='table table-sm'>";
echo "<tr><th>Table</th><th>Lignes</th><th>Taille</th><th>Statut</th></tr>";

foreach($tables as $table) {
    try {
        // Get table structure
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $sql .= "\n-- Table: $table\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $row['Create Table'] . ";\n\n";
        
        // Get data
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        $totalRows += $count;
        
        // Get size
        $sizeStmt = $pdo->query("
            SELECT ROUND(((data_length + index_length) / 1024), 2) AS size_kb
            FROM information_schema.TABLES 
            WHERE table_schema = 'antigravity' AND table_name = '$table'
        ");
        $size = $sizeStmt->fetchColumn();
        $totalSize += $size;
        
        if($count > 0) {
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($rows as $dataRow) {
                $columns = array_keys($dataRow);
                $values = array_map(function($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote($v);
                }, array_values($dataRow));
                
                $sql .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }
        
        echo "<tr><td>$table</td><td>$count</td><td>{$size} KB</td><td>✓</td></tr>";
        
    } catch(PDOException $e) {
        echo "<tr class='table-danger'><td>$table</td><td colspan='3'>❌ " . $e->getMessage() . "</td></tr>";
    }
}

echo "</table>";

// Write backup
file_put_contents($backupFile, $sql);
$fileSize = filesize($backupFile);

echo "<hr>";
echo "<div class='alert alert-success'>";
echo "<h4>✅ BACKUP COMPLET CRÉÉ</h4>";
echo "<p><strong>Fichier:</strong> <code>$backupFile</code></p>";
echo "<p><strong>Taille:</strong> " . number_format($fileSize/1024, 2) . " KB</p>";
echo "<p><strong>Tables:</strong> " . count($tables) . "</p>";
echo "<p><strong>Lignes totales:</strong> " . number_format($totalRows) . "</p>";
echo "<p><strong>Taille BDD:</strong> " . number_format($totalSize, 2) . " KB</p>";
echo "</div>";

echo "<h4>🔐 RESTAURATION D'URGENCE</h4>";
echo "<pre>mysql -u root antigravity < $backupFile</pre>";

echo "<div class='alert alert-info'>";
echo "<strong>✅ Prêt pour nettoyage</strong><br>";
echo "Vous pouvez maintenant exécuter les actions 1, 2, 3 en toute sécurité.";
echo "</div>";

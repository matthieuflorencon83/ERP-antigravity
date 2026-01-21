<?php
// tools/backup_catalog_tables.php
require_once __DIR__ . '/../db.php';

$backupDir = __DIR__ . '/../backups';
if(!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$timestamp = date('Y-m-d_H-i-s');
$backupFile = "$backupDir/catalog_backup_$timestamp.sql";

echo "<h3>🛡️ BACKUP CATALOGUE - PROTOCOLE ZERO-DAY</h3>";

$tables = ['articles', 'familles_articles', 'sous_familles_articles', 'finitions', 'fabricants', 'familles'];

$sql = "-- ANTIGRAVITY CATALOG BACKUP\n";
$sql .= "-- Date: $timestamp\n";
$sql .= "-- Tables: " . implode(', ', $tables) . "\n\n";

foreach($tables as $table) {
    try {
        // Get CREATE TABLE
        $stmt = $pdo->query("SHOW CREATE TABLE $table");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $sql .= "\n-- Table: $table\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}_backup`;\n";
        $sql .= str_replace("CREATE TABLE `$table`", "CREATE TABLE `{$table}_backup`", $row['Create Table']) . ";\n\n";
        
        // Get data count
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "✓ $table ($count lignes)<br>";
        
        // Get INSERT statements
        if($count > 0) {
            $stmt = $pdo->query("SELECT * FROM $table");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($rows as $row) {
                $columns = array_keys($row);
                $values = array_map(function($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote($v);
                }, array_values($row));
                
                $sql .= "INSERT INTO `{$table}_backup` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }
        
    } catch(PDOException $e) {
        echo "⚠️ $table : " . $e->getMessage() . "<br>";
    }
}

// Write backup file
file_put_contents($backupFile, $sql);
$size = filesize($backupFile);

echo "<hr>";
echo "<div class='alert alert-success'>";
echo "<h4>✅ BACKUP CRÉÉ</h4>";
echo "<p><strong>Fichier:</strong> $backupFile</p>";
echo "<p><strong>Taille:</strong> " . number_format($size/1024, 2) . " KB</p>";
echo "<p><strong>Tables:</strong> " . count($tables) . "</p>";
echo "</div>";

echo "<h4>🔐 PROCÉDURE DE RESTAURATION</h4>";
echo "<pre>mysql -u root antigravity < $backupFile</pre>";

echo "<h4>📋 CONTENU DU BACKUP</h4>";
echo "<textarea style='width:100%; height:200px; font-family:monospace; font-size:11px;'>";
echo htmlspecialchars(substr($sql, 0, 2000));
echo "\n... (truncated)";
echo "</textarea>";

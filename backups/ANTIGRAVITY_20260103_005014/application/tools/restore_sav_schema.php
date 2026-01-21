<?php
require_once __DIR__ . '/../db.php';

echo "<h1>🔄 Restoring SAV Schema</h1>";

try {
    // 1. DROP Incorrect Table
    $pdo->exec("DROP TABLE IF EXISTS sav_interventions");
    echo "<p>✅ Dropped incorrect 'sav_interventions' table.</p>";

    // 2. READ & EXECUTE Backup SQL
    $sql_file = __DIR__ . '/../backups/backup_20260101_2342/files/install/sav_schema.sql';
    if (!file_exists($sql_file)) {
        die("❌ SQL file not found: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Split by statement (;) to handle creates properly if needed, but PDO exec usually runs batch if simple
    // Actually, simple tables creation is fine in one go or multiple execs.
    // Let's rely on simple exec since the file has SET FOREIGN_KEY_CHECKS
    
    $pdo->exec($sql_content);
    echo "<p>✅ Executed sav_schema.sql from backup.</p>";
    
} catch(PDOException $e) {
    echo "<h1>❌ Error: " . $e->getMessage() . "</h1>";
}

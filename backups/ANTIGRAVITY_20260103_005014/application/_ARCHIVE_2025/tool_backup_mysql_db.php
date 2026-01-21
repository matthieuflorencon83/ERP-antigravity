<?php
// tool_backup_mysql_db.php
// Script de backup spécial pour la base 'mysql'
require 'db.php'; // Pour les credentials root

// Configuration
$db_name = 'mysql';
$backup_file = __DIR__ . '/backups/backup_mysql_polluted_' . date('Y-m-d_H-i-s') . '.sql';

// Vérification dossier backups
if (!is_dir(__DIR__ . '/backups')) {
    mkdir(__DIR__ . '/backups');
}

echo "<h1>Démarrage Backup Base Système '$db_name'</h1>";

try {
    // Connexion Spécifique
    $pdo_sys = new PDO("mysql:host={$db_config['host']};dbname=$db_name;charset=utf8mb4", $db_config['user'], $db_config['pass']);
    $pdo_sys->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lister les tables
    $tables = $pdo_sys->query("SHOW FULL TABLES")->fetchAll(PDO::FETCH_NUM);
    
    $sql_output = "-- BACKUP BASE SYSTEME : $db_name \n";
    $sql_output .= "-- DATE : " . date('Y-m-d H:i:s') . "\n";
    $sql_output .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $sql_output .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n";

    foreach ($tables as $row) {
        $table = $row[0];
        $type = $row[1]; // BASE TABLE or VIEW

        // Skip Views for now if complex, but good to have
        if ($type == 'VIEW') {
            $sql_output .= "-- VIEW: $table skipped in simple backup logic \n";
            continue;
        }

        // Structure
        $stmt = $pdo_sys->query("SHOW CREATE TABLE `$table`");
        $create_row = $stmt->fetch(PDO::FETCH_NUM);
        $sql_output .= "\n-- Structure pour `$table`\n";
        $sql_output .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql_output .= $create_row[1] . ";\n";

        // Données
        // Attention : Certaines tables systèmes peuvent être lockées ou spéciales
        // On essaye, en catchant les erreurs
        try {
            $rows = $pdo_sys->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                $sql_output .= "\n-- Données pour `$table`\n";
                foreach ($rows as $r) {
                    $keys = array_map(function($k){ return "`$k`"; }, array_keys($r));
                    $values = array_map(function($v){ 
                        if ($v === null) return "NULL";
                        return "'" . addslashes($v) . "'"; 
                    }, array_values($r));
                    
                    $sql_output .= "INSERT INTO `$table` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
                }
            }
        } catch (Exception $e) {
            $sql_output .= "-- ERREUR DUMP DONNEES `$table` : " . $e->getMessage() . "\n";
        }
    }

    $sql_output .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

    // Écriture Fichier
    if (file_put_contents($backup_file, $sql_output)) {
        echo "<div style='color:green; font-weight:bold;'>✅ Backup Réussi !</div>";
        echo "<p>Fichier : $backup_file (" . filesize($backup_file) . " octets)</p>";
    } else {
        echo "<div style='color:red;'>❌ Erreur écriture fichier backup.</div>";
    }

} catch (Exception $e) {
    die("<div style='color:red;'>ERREUR CRITIQUE : " . $e->getMessage() . "</div>");
}
?>

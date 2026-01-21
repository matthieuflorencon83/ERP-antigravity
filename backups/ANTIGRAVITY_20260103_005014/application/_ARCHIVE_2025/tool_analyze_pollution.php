<?php
// tool_analyze_pollution.php
require 'db.php';

echo "<h1>Analyse Forensique Base 'mysql'</h1>";

// 1. Connexion 'mysql'
try {
    $pdo_sys = new PDO("mysql:host={$db_config['host']};dbname=mysql;charset=utf8mb4", $db_config['user'], $db_config['pass']);
    $pdo_sys->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. Lister TOUTES les tables via requete SQL native (Plus fiable que SHOW TABLES parfois)
    // Nous utilisons INFORMATION_SCHEMA pour être sûrs
    $stmt = $pdo_sys->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'mysql'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 3. Whitelist (Tables système standard MySQL 5.7 / 8.0)
    $whitelist = [
        'columns_priv', 'db', 'engine_cost', 'event', 'func', 
        'general_log', 'global_grants', 'gtid_executed', 
        'help_category', 'help_keyword', 'help_relation', 'help_topic', 
        'innodb_index_stats', 'innodb_table_stats', 'ndb_binlog_index', 
        'password_history', 'plugin', 'proc', 'procs_priv', 'proxies_priv', 
        'role_edges', 'server_cost', 'servers', 'slave_master_info', 
        'slave_relay_log_info', 'slave_worker_info', 'slow_log', 
        'tables_priv', 'time_zone', 'time_zone_leap_second', 'time_zone_name', 
        'time_zone_transition', 'time_zone_transition_type', 'user',
        'component', 'default_roles', 'password_history', 'replication_asynchronous_connection_failover',
        'replication_asynchronous_connection_failover_managed',
        'replication_group_configuration_version', 'replication_group_member_actions'
        // Add Laragon specific ?
    ];
    
    $intruders = [];
    $system_tables = [];
    
    foreach($tables as $t) {
        if (in_array($t, $whitelist)) {
            $system_tables[] = $t;
        } else {
            $intruders[] = $t;
        }
    }
    
    echo "<h3>RÉSULTAT DU SCAN</h3>";
    echo "Total tables trouvées : " . count($tables) . "<br>";
    echo "Tables Système (Whitelist) : " . count($system_tables) . "<br>";
    echo "<strong>Tables Intrues (Pollution) : " . count($intruders) . "</strong><br>";
    
    if (count($intruders) > 0) {
        echo "<hr><h4 style='color:red'>🛑 INTRUS DÉTECTÉS :</h4><ul>";
        foreach($intruders as $intrus) {
            echo "<li>$intrus</li>";
        }
        echo "</ul>";
        
        // Generate Cleanup SQL
        echo "<hr><h4>🔧 SCRIPT DE NETTOYAGE SUGGÉRÉ (À VÉRIFIER) :</h4>";
        echo "<textarea rows='10' cols='80'>";
        echo "-- SAUVEGARDEZ D'ABORD ! \n";
        echo "USE mysql;\n";
        echo "SET FOREIGN_KEY_CHECKS = 0;\n";
        foreach($intruders as $intrus) {
            echo "DROP TABLE IF EXISTS `$intrus`;\n";
        }
        echo "SET FOREIGN_KEY_CHECKS = 1;\n";
        echo "</textarea>";
    } else {
        echo "<h4 style='color:green'>✅ Base propre. Aucun intrus détecté.</h4>";
        echo "Verifiez si vous ne vous trompez pas de base dans le code PHP.";
    }

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}
?>

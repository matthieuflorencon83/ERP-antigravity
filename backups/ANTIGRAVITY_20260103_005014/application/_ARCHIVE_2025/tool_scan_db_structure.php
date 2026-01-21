<?php
// Utilisation de la connexion root native
require 'db.php'; // Gives $pdo connected to 'antigravity'
echo "<h1>Diagnostic DBA</h1>";

// 1. Lister les Bases
echo "<h2>1. Serveur : Liste des Bases</h2>";
$stmt = $pdo->query("SHOW DATABASES");
$dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<ul>";
foreach($dbs as $db) {
    echo "<li>$db</li>";
}
echo "</ul>";

// 2. Vérifier contenu 'mysql'
echo "<h2>2. Inspection Base 'mysql'</h2>";
try {
    // Connexion à 'mysql'
    // Note: root user usually has access
    $pdo_sys = new PDO("mysql:host={$db_config['host']};dbname=mysql;charset=utf8mb4", $db_config['user'], $db_config['pass']);
    $stmt = $pdo_sys->query("SHOW TABLES LIKE 'clients%'"); // On cherche des tables métiers
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<div style='color:red'><strong>ALERTE : Tables métier trouvées dans 'mysql' !</strong></div>";
        print_r($tables);
    } else {
        echo "<div style='color:green'>Aucune table 'clients' détectée dans 'mysql'.</div>";
        
        // Check random other ones
        $stmt = $pdo_sys->query("SHOW TABLES LIKE 'affaires%'");
        $t2 = $stmt->fetchAll();
        if ($t2) echo "<div style='color:red'>Table 'affaires' trouvée !</div>";
    }
    
    // Check row count comparison
    echo "<h3>Comparatif rapide (Si tables existent)</h3>";
    // Si 'utilisateurs' existe dans les deux ?
    
} catch (Exception $e) {
    echo "Erreur accès mysql: " . $e->getMessage();
}

// 3. Vérifier Tables dans 'antigravity'
echo "<h2>3. Tables dans 'antigravity'</h2>";
$stmt = $pdo->query("SHOW TABLES");
$tables_ag = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($tables_ag);
?>

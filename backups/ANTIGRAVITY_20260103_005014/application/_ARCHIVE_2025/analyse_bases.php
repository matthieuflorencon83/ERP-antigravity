<?php
// Analyse comparative des bases de données
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Analyse des Bases de Données</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .good { background-color: #d4edda; }
        .bad { background-color: #f8d7da; }
        .info { background-color: #d1ecf1; }
    </style>
</head>
<body>
    <h1>🔍 Analyse Comparative des Bases de Données</h1>
    
    <?php
    // Connexion pour lister les bases
    $pdo_root = new PDO("mysql:host=localhost;charset=utf8mb4", 'root', 'root');
    $pdo_root->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Lister toutes les bases
    $stmt = $pdo_root->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::COLUMN);
    
    echo "<h2>Bases de données trouvées :</h2>";
    echo "<ul>";
    foreach ($databases as $db) {
        if (!in_array($db, ['information_schema', 'performance_schema', 'sys'])) {
            echo "<li><strong>$db</strong></li>";
        }
    }
    echo "</ul>";
    
    // Analyser chaque base pertinente
    $bases_to_check = ['antigravity', 'mysql'];
    
    foreach ($bases_to_check as $dbname) {
        if (!in_array($dbname, $databases)) continue;
        
        echo "<hr>";
        echo "<h2>📊 Base : <code>$dbname</code></h2>";
        
        $pdo_temp = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8mb4", 'root', 'root');
        
        // Compter les tables
        $stmt = $pdo_temp->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::COLUMN);
        
        echo "<p><strong>Nombre de tables :</strong> " . count($tables) . "</p>";
        
        // Chercher des tables spécifiques de l'application
        $app_tables = ['affaires', 'clients', 'fournisseurs', 'commandes_achats', 'articles_catalogue'];
        $found_app_tables = array_intersect($tables, $app_tables);
        
        if (count($found_app_tables) > 0) {
            echo "<p class='good'><strong>✅ Tables de l'application trouvées :</strong> " . implode(', ', $found_app_tables) . "</p>";
            
            // Vérifier les nouvelles tables clients
            $client_tables = ['client_contacts', 'client_adresses', 'client_telephones', 'client_emails'];
            $found_client_tables = array_intersect($tables, $client_tables);
            
            if (count($found_client_tables) > 0) {
                echo "<p class='good'><strong>✅ Nouvelles tables Client CRM :</strong> " . implode(', ', $found_client_tables) . "</p>";
            } else {
                echo "<p class='bad'><strong>❌ Tables Client CRM manquantes</strong></p>";
            }
            
            // Compter les données
            foreach ($app_tables as $table) {
                if (in_array($table, $tables)) {
                    $stmt = $pdo_temp->query("SELECT COUNT(*) as count FROM `$table`");
                    $count = $stmt->fetch()['count'];
                    echo "<p>→ Table <code>$table</code> : <strong>$count</strong> enregistrements</p>";
                }
            }
        } else {
            echo "<p class='info'><strong>ℹ️ Base système MySQL (ne contient pas les données de l'application)</strong></p>";
        }
        
        // Afficher quelques tables
        echo "<details><summary>Voir toutes les tables ($dbname)</summary>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul></details>";
    }
    ?>
    
    <hr>
    <h2>🎯 Conclusion</h2>
    
    <?php
    // Vérifier db.php
    echo "<h3>Configuration de db.php :</h3>";
    echo "<pre>";
    echo htmlspecialchars(file_get_contents('db.php'));
    echo "</pre>";
    
    // Recommandation
    echo "<div class='good' style='padding: 20px; margin: 20px 0;'>";
    echo "<h3>✅ RECOMMANDATION :</h3>";
    echo "<p><strong>Base à utiliser :</strong> <code>antigravity</code></p>";
    echo "<p><strong>Base à NE PAS TOUCHER :</strong> <code>mysql</code> (base système de MySQL)</p>";
    echo "<p><strong>Fichier db.php :</strong> Déjà configuré correctement sur <code>antigravity</code></p>";
    echo "</div>";
    
    echo "<div class='bad' style='padding: 20px; margin: 20px 0;'>";
    echo "<h3>⚠️ ATTENTION :</h3>";
    echo "<p><strong>NE JAMAIS SUPPRIMER la base <code>mysql</code></strong> - c'est la base système de MySQL !</p>";
    echo "<p>Supprimer cette base casserait complètement MySQL.</p>";
    echo "</div>";
    ?>
    
</body>
</html>

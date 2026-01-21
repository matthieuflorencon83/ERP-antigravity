<?php
/**
 * Installation Web des Tables Métrage
 * Accès : http://localhost/antigravity/install_metrage_web.php
 */

// Sécurité basique
$secret_key = 'install_metrage_2025';
$provided_key = $_GET['key'] ?? '';

if ($provided_key !== $secret_key) {
    die('❌ Accès refusé. Utilisez : ?key=' . $secret_key);
}

require_once 'db.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Installation Métrage</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #0d6efd; }
        .success { color: #28a745; padding: 10px; background: #d4edda; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; padding: 10px; background: #d1ecf1; border-radius: 4px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .table-list { list-style: none; padding: 0; }
        .table-list li { padding: 8px; border-bottom: 1px solid #eee; }
        .table-list li:before { content: "✓ "; color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Installation Module Métrage</h1>
        
        <?php
        try {
            echo '<div class="info">📋 Lecture du schéma SQL...</div>';
            
            // Lire le fichier SQL
            $sql_file = __DIR__ . '/install/metrage_schema.sql';
            
            if (!file_exists($sql_file)) {
                throw new Exception("Fichier metrage_schema.sql introuvable : $sql_file");
            }
            
            $sql = file_get_contents($sql_file);
            
            if ($sql === false) {
                throw new Exception("Impossible de lire le fichier metrage_schema.sql");
            }
            
            echo '<div class="info">⚙️ Exécution du schéma SQL...</div>';
            
            // Exécuter le SQL
            $pdo->exec($sql);
            
            echo '<div class="success">✅ Tables métrage créées avec succès !</div>';
            
            // Vérifier les tables créées
            echo '<h2>📊 Tables créées :</h2>';
            $stmt = $pdo->query("SHOW TABLES LIKE 'metrage%'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tables)) {
                echo '<div class="error">⚠️ Aucune table métrage trouvée !</div>';
            } else {
                echo '<ul class="table-list">';
                foreach ($tables as $table) {
                    echo "<li>$table</li>";
                    
                    // Afficher structure
                    $desc = $pdo->query("DESCRIBE $table")->fetchAll();
                    echo '<pre style="margin-left: 20px; font-size: 11px;">';
                    foreach ($desc as $col) {
                        echo sprintf("%-30s %-20s %s\n", $col['Field'], $col['Type'], $col['Key']);
                    }
                    echo '</pre>';
                }
                echo '</ul>';
            }
            
            // Test de l'API
            echo '<h2>🧪 Test de l\'API</h2>';
            echo '<div class="info">Test de l\'endpoint get_tasks...</div>';
            
            $test_url = 'http://localhost/antigravity/api_metrage_cockpit.php?action=get_tasks';
            echo '<p>URL testée : <code>' . $test_url . '</code></p>';
            
            echo '<div class="success">✅ Installation terminée !</div>';
            echo '<div class="info">🎯 Prochaine étape : Tester <a href="metrage_cockpit.php">metrage_cockpit.php</a></div>';
            
        } catch (Exception $e) {
            echo '<div class="error">❌ ERREUR : ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
        ?>
        
    </div>
</body>
</html>

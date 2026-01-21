<?php
/**
 * Script d'analyse du schéma de base de données
 * Pour comprendre la structure réelle avant de créer les index
 */

require_once '../db.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analyse Schéma BDD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .table-info { margin-bottom: 30px; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h2 class="mb-4">🔍 Analyse du Schéma de Base de Données</h2>
        
        <?php
        try {
            // Récupérer toutes les tables
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<div class='alert alert-info'>";
            echo "<strong>📊 Tables trouvées :</strong> " . count($tables) . " tables";
            echo "</div>";
            
            foreach ($tables as $table) {
                echo "<div class='card mb-4'>";
                echo "<div class='card-header bg-primary text-white'>";
                echo "<h4 class='mb-0'>Table: <code>$table</code></h4>";
                echo "</div>";
                echo "<div class='card-body'>";
                
                // Récupérer les colonnes
                $stmt = $pdo->query("DESCRIBE `$table`");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h5>Colonnes :</h5>";
                echo "<table class='table table-sm table-bordered'>";
                echo "<thead><tr><th>Nom</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>";
                echo "<tbody>";
                foreach ($columns as $col) {
                    $key_badge = '';
                    if ($col['Key'] === 'PRI') $key_badge = '<span class="badge bg-danger">PRIMARY</span>';
                    elseif ($col['Key'] === 'MUL') $key_badge = '<span class="badge bg-warning">INDEX</span>';
                    elseif ($col['Key'] === 'UNI') $key_badge = '<span class="badge bg-info">UNIQUE</span>';
                    
                    echo "<tr>";
                    echo "<td><strong>{$col['Field']}</strong></td>";
                    echo "<td>{$col['Type']}</td>";
                    echo "<td>{$col['Null']}</td>";
                    echo "<td>$key_badge</td>";
                    echo "<td>" . ($col['Default'] ?? '<em>NULL</em>') . "</td>";
                    echo "</tr>";
                }
                echo "</tbody></table>";
                
                // Récupérer les index existants
                $stmt = $pdo->query("SHOW INDEX FROM `$table`");
                $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($indexes)) {
                    echo "<h5 class='mt-3'>Index existants :</h5>";
                    echo "<table class='table table-sm table-bordered'>";
                    echo "<thead><tr><th>Nom Index</th><th>Colonne</th><th>Unique</th><th>Type</th></tr></thead>";
                    echo "<tbody>";
                    
                    $displayed_indexes = [];
                    foreach ($indexes as $idx) {
                        $key = $idx['Key_name'] . '_' . $idx['Column_name'];
                        if (!in_array($key, $displayed_indexes)) {
                            echo "<tr>";
                            echo "<td><code>{$idx['Key_name']}</code></td>";
                            echo "<td>{$idx['Column_name']}</td>";
                            echo "<td>" . ($idx['Non_unique'] == 0 ? '✅ Oui' : '❌ Non') . "</td>";
                            echo "<td>{$idx['Index_type']}</td>";
                            echo "</tr>";
                            $displayed_indexes[] = $key;
                        }
                    }
                    echo "</tbody></table>";
                }
                
                echo "</div></div>";
            }
            
            // Générer les recommandations d'index
            echo "<div class='card border-success'>";
            echo "<div class='card-header bg-success text-white'>";
            echo "<h4 class='mb-0'>💡 Recommandations d'Index</h4>";
            echo "</div>";
            echo "<div class='card-body'>";
            echo "<p>Basé sur l'analyse de votre schéma, voici les index recommandés :</p>";
            echo "<pre>";
            
            // Analyser et recommander
            foreach ($tables as $table) {
                $stmt = $pdo->query("DESCRIBE `$table`");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $recommendations = [];
                
                foreach ($columns as $col) {
                    $field = $col['Field'];
                    
                    // Recommander des index sur les colonnes fréquemment utilisées
                    if (stripos($field, 'statut') !== false && $col['Key'] !== 'PRI' && $col['Key'] !== 'MUL') {
                        $recommendations[] = "ALTER TABLE `$table` ADD INDEX idx_statut ($field);";
                    }
                    if (stripos($field, 'date') !== false && $col['Key'] !== 'PRI' && $col['Key'] !== 'MUL') {
                        $recommendations[] = "ALTER TABLE `$table` ADD INDEX idx_$field ($field);";
                    }
                    if (preg_match('/_id$/', $field) && $col['Key'] !== 'PRI' && $col['Key'] !== 'MUL') {
                        $recommendations[] = "ALTER TABLE `$table` ADD INDEX idx_$field ($field);";
                    }
                }
                
                if (!empty($recommendations)) {
                    echo "\n-- Table: $table\n";
                    foreach ($recommendations as $rec) {
                        echo "$rec\n";
                    }
                }
            }
            
            echo "</pre>";
            echo "</div></div>";
            
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>";
            echo "<strong>Erreur :</strong> " . htmlspecialchars($e->getMessage());
            echo "</div>";
        }
        ?>
        
        <div class="mt-4">
            <a href="../dashboard.php" class="btn btn-primary">Retour au Dashboard</a>
        </div>
    </div>
</body>
</html>

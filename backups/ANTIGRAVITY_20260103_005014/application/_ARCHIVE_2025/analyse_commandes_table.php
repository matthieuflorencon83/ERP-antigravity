<?php
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Analyse Table Commandes</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #2c3e50; color: white; }
        .success { background-color: #d4edda; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .error { background-color: #f8d7da; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .warning { background-color: #fff3cd; padding: 20px; margin: 20px 0; border-radius: 5px; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Analyse de la table commandes_achats</h1>
    
    <?php
    try {
        // 1. Vérifier la structure actuelle
        echo "<h2>1. Structure actuelle de la table</h2>";
        $stmt = $pdo->query("DESCRIBE commandes_achats");
        $columns = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        $existing_columns = [];
        foreach ($columns as $col) {
            $existing_columns[] = $col['Field'];
            echo "<tr>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 2. Colonnes demandées
        echo "<h2>2. Colonnes demandées vs existantes</h2>";
        
        $colonnes_demandees = [
            'date_en_attente' => 'DATE - Date de mise en attente',
            'date_commande' => 'DATE - Date de commande (existe déjà ?)',
            'date_arc_recu' => 'DATE - Date de réception de l\'ARC (Accusé Réception Commande)',
            'date_livraison_prevue' => 'DATE - Date de livraison prévue',
            'date_livraison_reelle' => 'DATE - Date de livraison réelle'
        ];
        
        echo "<table>";
        echo "<tr><th>Colonne demandée</th><th>Description</th><th>Existe ?</th></tr>";
        
        $colonnes_a_ajouter = [];
        foreach ($colonnes_demandees as $col => $desc) {
            $existe = in_array($col, $existing_columns);
            echo "<tr>";
            echo "<td><strong>$col</strong></td>";
            echo "<td>$desc</td>";
            echo "<td>" . ($existe ? "✅ OUI" : "❌ NON") . "</td>";
            echo "</tr>";
            
            if (!$existe) {
                $colonnes_a_ajouter[] = $col;
            }
        }
        echo "</table>";
        
        // 3. Proposition de script SQL
        if (count($colonnes_a_ajouter) > 0) {
            echo "<div class='warning'>";
            echo "<h3>⚠️ Colonnes à ajouter : " . count($colonnes_a_ajouter) . "</h3>";
            echo "<ul>";
            foreach ($colonnes_a_ajouter as $col) {
                echo "<li><strong>$col</strong></li>";
            }
            echo "</ul>";
            echo "</div>";
            
            echo "<h3>Script SQL à exécuter :</h3>";
            echo "<pre>";
            echo "ALTER TABLE commandes_achats\n";
            $alters = [];
            foreach ($colonnes_a_ajouter as $col) {
                $alters[] = "ADD COLUMN $col DATE DEFAULT NULL";
            }
            echo implode(",\n", $alters) . ";";
            echo "</pre>";
            
            // 4. Formulaire pour exécuter
            echo "<form method='POST'>";
            echo "<button type='submit' name='execute' style='padding: 12px 24px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>✅ Exécuter les modifications</button>";
            echo "</form>";
        } else {
            echo "<div class='success'>";
            echo "<h3>✅ Toutes les colonnes existent déjà !</h3>";
            echo "</div>";
        }
        
        // 5. Exécution si demandé
        if (isset($_POST['execute']) && count($colonnes_a_ajouter) > 0) {
            echo "<div class='warning'>";
            echo "<h3>🔄 Exécution en cours...</h3>";
            
            try {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                foreach ($colonnes_a_ajouter as $col) {
                    $sql = "ALTER TABLE commandes_achats ADD COLUMN $col DATE DEFAULT NULL";
                    $pdo->exec($sql);
                    echo "<p style='color: green;'>✅ Colonne <strong>$col</strong> ajoutée</p>";
                }
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                echo "<p style='color: green; font-weight: bold;'>✅ Toutes les colonnes ont été ajoutées avec succès !</p>";
                echo "<p><a href='dashboard.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>→ Retour au Dashboard</a></p>";
                echo "</div>";
                
            } catch (PDOException $e) {
                echo "<div class='error'>";
                echo "<h3>❌ Erreur lors de l'exécution</h3>";
                echo "<p>" . $e->getMessage() . "</p>";
                echo "</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<h3>❌ Erreur</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
    ?>
    
</body>
</html>

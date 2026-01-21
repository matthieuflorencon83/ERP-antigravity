<?php
require_once 'db.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vérification Colonnes</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #2c3e50; color: white; }
        .success { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .highlight { background-color: #ffffcc; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Vérification des colonnes - commandes_achats</h1>
        
        <?php
        try {
            $stmt = $pdo->query("DESCRIBE commandes_achats");
            $columns = $stmt->fetchAll();
            
            echo "<h2>📋 Toutes les colonnes de la table (" . count($columns) . " colonnes)</h2>";
            
            $colonnes_dates = ['date_en_attente', 'date_commande', 'date_arc_recu', 'date_prevue_cible', 'date_livraison_reelle'];
            
            echo "<table>";
            echo "<tr><th>#</th><th>Nom de la colonne</th><th>Type</th><th>Null</th><th>Default</th><th>Statut</th></tr>";
            
            $found_dates = [];
            $i = 1;
            foreach ($columns as $col) {
                $is_date_column = in_array($col['Field'], $colonnes_dates);
                $row_class = $is_date_column ? 'class="highlight"' : '';
                
                echo "<tr $row_class>";
                echo "<td>$i</td>";
                echo "<td><strong>{$col['Field']}</strong></td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
                echo "<td>";
                
                if ($is_date_column) {
                    echo "✅ <strong>COLONNE REQUISE</strong>";
                    $found_dates[] = $col['Field'];
                } else {
                    echo "-";
                }
                
                echo "</td>";
                echo "</tr>";
                $i++;
            }
            echo "</table>";
            
            // Résumé
            echo "<h2>📊 Résumé</h2>";
            
            echo "<h3>Colonnes requises trouvées :</h3>";
            echo "<ul>";
            foreach ($colonnes_dates as $col_name) {
                $found = in_array($col_name, $found_dates);
                echo "<li>" . ($found ? "✅" : "❌") . " <strong>$col_name</strong></li>";
            }
            echo "</ul>";
            
            $missing = array_diff($colonnes_dates, $found_dates);
            
            if (count($missing) > 0) {
                echo "<div class='error'>";
                echo "<h3>❌ Colonnes manquantes : " . count($missing) . "</h3>";
                echo "<p>Les colonnes suivantes n'existent pas encore :</p>";
                echo "<ul>";
                foreach ($missing as $col) {
                    echo "<li><strong>$col</strong></li>";
                }
                echo "</ul>";
                
                echo "<h4>Script SQL pour les créer :</h4>";
                echo "<pre style='background:#2c3e50;color:#ecf0f1;padding:15px;border-radius:5px;'>";
                foreach ($missing as $col) {
                    echo "ALTER TABLE commandes_achats ADD COLUMN $col DATE DEFAULT NULL;\n";
                }
                echo "</pre>";
                
                echo "<form method='POST'>";
                echo "<button type='submit' name='create_columns' style='padding:12px 24px;background:#28a745;color:white;border:none;border-radius:5px;cursor:pointer;font-size:16px;font-weight:bold;'>🔧 Créer les colonnes manquantes</button>";
                echo "</form>";
                echo "</div>";
                
            } else {
                echo "<div class='success'>";
                echo "<h3>✅ Toutes les colonnes requises sont présentes !</h3>";
                echo "<p>La table <code>commandes_achats</code> contient toutes les colonnes de dates nécessaires.</p>";
                echo "</div>";
            }
            
            // Traitement de la création
            if (isset($_POST['create_columns']) && count($missing) > 0) {
                echo "<div class='warning'>";
                echo "<h3>🔄 Création des colonnes en cours...</h3>";
                
                foreach ($missing as $col) {
                    try {
                        $sql = "ALTER TABLE commandes_achats ADD COLUMN $col DATE DEFAULT NULL";
                        $pdo->exec($sql);
                        echo "<p style='color:green;'>✅ Colonne <strong>$col</strong> créée avec succès</p>";
                    } catch (PDOException $e) {
                        echo "<p style='color:red;'>❌ Erreur sur <strong>$col</strong> : " . $e->getMessage() . "</p>";
                    }
                }
                
                echo "<p><strong>Rafraîchissez la page pour voir les changements.</strong></p>";
                echo "<p><a href='verify_commandes_columns.php' style='padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>🔄 Rafraîchir</a></p>";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<h3>❌ Erreur</h3>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "</div>";
        }
        ?>
        
        <hr>
        <p><a href="dashboard.php" style="padding:10px 20px;background:#6c757d;color:white;text-decoration:none;border-radius:5px;">← Retour au Dashboard</a></p>
    </div>
</body>
</html>

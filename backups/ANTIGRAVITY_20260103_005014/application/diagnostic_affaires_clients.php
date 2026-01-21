<?php
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Analyse Affaires - Clients</title>
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
    <h1>🔍 Analyse : Liaison Affaires ↔ Clients</h1>
    
    <?php
    try {
        // 1. Vérifier la structure de la table affaires
        echo "<h2>1. Structure de la table 'affaires'</h2>";
        $stmt = $pdo->query("DESCRIBE affaires");
        $affaires_columns = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        
        $has_client_id = false;
        foreach ($affaires_columns as $col) {
            echo "<tr>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "</tr>";
            
            if ($col['Field'] === 'client_id') {
                $has_client_id = true;
            }
        }
        echo "</table>";
        
        // 2. Vérifier si client_id existe
        if (!$has_client_id) {
            echo "<div class='error'>";
            echo "<h3>❌ PROBLÈME DÉTECTÉ</h3>";
            echo "<p>La colonne <code>client_id</code> n'existe PAS dans la table <code>affaires</code> !</p>";
            echo "<p><strong>Solution :</strong> Ajouter la colonne client_id</p>";
            echo "</div>";
            
            echo "<h3>Commande SQL à exécuter :</h3>";
            echo "<pre>ALTER TABLE affaires ADD COLUMN client_id INT AFTER id;</pre>";
            
            echo "<form method='POST'>";
            echo "<button type='submit' name='add_client_id' style='padding: 12px 24px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>✅ Ajouter la colonne client_id</button>";
            echo "</form>";
        } else {
            echo "<div class='success'>";
            echo "<h3>✅ La colonne client_id existe</h3>";
            echo "</div>";
        }
        
        // 3. Vérifier les données existantes
        echo "<h2>2. Échantillon de données (5 premières affaires)</h2>";
        $stmt = $pdo->query("SELECT * FROM affaires LIMIT 5");
        $affaires = $stmt->fetchAll();
        
        if (count($affaires) > 0) {
            echo "<table>";
            echo "<tr>";
            foreach (array_keys($affaires[0]) as $key) {
                if (!is_numeric($key)) {
                    echo "<th>$key</th>";
                }
            }
            echo "</tr>";
            
            foreach ($affaires as $affaire) {
                echo "<tr>";
                foreach ($affaire as $key => $value) {
                    if (!is_numeric($key)) {
                        echo "<td>" . htmlspecialchars($value ?? '-') . "</td>";
                    }
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>Aucune affaire dans la base de données.</p>";
        }
        
        // 4. Vérifier les clients
        echo "<h2>3. Clients disponibles</h2>";
        $stmt = $pdo->query("SELECT id, nom_principal, prenom, email_principal FROM clients LIMIT 5");
        $clients = $stmt->fetchAll();
        
        if (count($clients) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th></tr>";
            foreach ($clients as $client) {
                echo "<tr>";
                echo "<td>{$client['id']}</td>";
                echo "<td>" . htmlspecialchars($client['nom_principal'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($client['prenom'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($client['email_principal'] ?? '-') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>Aucun client dans la base de données.</p>";
        }
        
        // 5. Proposition de solution
        echo "<div class='warning'>";
        echo "<h2>💡 Solution Recommandée</h2>";
        echo "<ol>";
        echo "<li><strong>Ajouter la colonne client_id</strong> dans la table affaires (si manquante)</li>";
        echo "<li><strong>Créer une foreign key</strong> pour lier affaires.client_id → clients.id</li>";
        echo "<li><strong>Mettre à jour affaires_detail.php</strong> pour afficher les infos client via JOIN</li>";
        echo "</ol>";
        echo "</div>";
        
        // Traitement du formulaire
        if (isset($_POST['add_client_id'])) {
            try {
                $pdo->exec("ALTER TABLE affaires ADD COLUMN client_id INT AFTER id");
                echo "<div class='success'>";
                echo "<h3>✅ Colonne client_id ajoutée avec succès !</h3>";
                echo "<p>Rafraîchissez la page pour voir les changements.</p>";
                echo "</div>";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                    echo "<div class='warning'><p>⚠️ La colonne existe déjà</p></div>";
                } else {
                    echo "<div class='error'><p>❌ Erreur : " . $e->getMessage() . "</p></div>";
                }
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

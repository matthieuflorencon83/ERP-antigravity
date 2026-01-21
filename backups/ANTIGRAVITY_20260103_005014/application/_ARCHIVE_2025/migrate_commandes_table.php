<?php
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Migration Commandes</title>";
echo "<style>body{font-family:Arial;padding:20px;} table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:12px;} th{background:#2c3e50;color:white;} .success{background:#d4edda;padding:20px;margin:20px 0;border-radius:5px;} .error{background:#f8d7da;padding:20px;margin:20px 0;border-radius:5px;} pre{background:#2c3e50;color:#ecf0f1;padding:15px;border-radius:5px;}</style>";
echo "</head><body>";

echo "<h1>🔍 Analyse et Migration - Table commandes_achats</h1>";

try {
    // ÉTAPE 1 : Analyser la structure actuelle
    echo "<h2>Étape 1 : Structure actuelle</h2>";
    $stmt = $pdo->query("DESCRIBE commandes_achats");
    $columns = $stmt->fetchAll();
    
    $existing_columns = [];
    echo "<table><tr><th>Colonne</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        $existing_columns[] = $col['Field'];
        echo "<tr><td><strong>{$col['Field']}</strong></td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>" . ($col['Default'] ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Total colonnes existantes :</strong> " . count($existing_columns) . "</p>";
    
    // ÉTAPE 2 : Définir les colonnes requises
    echo "<h2>Étape 2 : Colonnes requises</h2>";
    
    $colonnes_requises = [
        'date_en_attente' => ['type' => 'DATE', 'description' => 'Date de mise en attente de la commande'],
        'date_commande' => ['type' => 'DATE', 'description' => 'Date d\'envoi de la commande au fournisseur'],
        'date_arc_recu' => ['type' => 'DATE', 'description' => 'Date de réception de l\'ARC (Accusé Réception Commande)'],
        'date_prevue_cible' => ['type' => 'DATE', 'description' => 'Date de livraison prévue/cible'],
        'date_livraison_reelle' => ['type' => 'DATE', 'description' => 'Date de livraison effective']
    ];
    
    echo "<table><tr><th>Colonne</th><th>Type</th><th>Description</th><th>Statut</th></tr>";
    
    $colonnes_a_ajouter = [];
    foreach ($colonnes_requises as $nom => $info) {
        $existe = in_array($nom, $existing_columns);
        echo "<tr>";
        echo "<td><strong>$nom</strong></td>";
        echo "<td>{$info['type']}</td>";
        echo "<td>{$info['description']}</td>";
        echo "<td>" . ($existe ? "<span style='color:green;'>✅ Existe</span>" : "<span style='color:red;'>❌ Manquante</span>") . "</td>";
        echo "</tr>";
        
        if (!$existe) {
            $colonnes_a_ajouter[$nom] = $info;
        }
    }
    echo "</table>";
    
    // ÉTAPE 3 : Créer les colonnes manquantes
    if (count($colonnes_a_ajouter) > 0) {
        echo "<div style='background:#fff3cd;padding:20px;margin:20px 0;border-radius:5px;'>";
        echo "<h2>Étape 3 : Création des colonnes manquantes</h2>";
        echo "<p><strong>" . count($colonnes_a_ajouter) . " colonne(s) à créer</strong></p>";
        
        echo "<h3>Script SQL généré :</h3>";
        echo "<pre>";
        foreach ($colonnes_a_ajouter as $nom => $info) {
            echo "ALTER TABLE commandes_achats ADD COLUMN $nom {$info['type']} DEFAULT NULL;\n";
        }
        echo "</pre>";
        
        // Exécution automatique
        echo "<h3>🔄 Exécution...</h3>";
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        $success_count = 0;
        foreach ($colonnes_a_ajouter as $nom => $info) {
            try {
                $sql = "ALTER TABLE commandes_achats ADD COLUMN $nom {$info['type']} DEFAULT NULL";
                $pdo->exec($sql);
                echo "<p style='color:green;'>✅ <strong>$nom</strong> créée avec succès</p>";
                $success_count++;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                    echo "<p style='color:orange;'>⚠️ <strong>$nom</strong> existe déjà</p>";
                } else {
                    echo "<p style='color:red;'>❌ Erreur sur <strong>$nom</strong> : " . $e->getMessage() . "</p>";
                }
            }
        }
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        echo "</div>";
        
        if ($success_count > 0) {
            echo "<div class='success'>";
            echo "<h2>✅ Migration réussie !</h2>";
            echo "<p><strong>$success_count</strong> colonne(s) ajoutée(s) avec succès</p>";
            echo "</div>";
        }
        
    } else {
        echo "<div class='success'>";
        echo "<h2>✅ Aucune migration nécessaire</h2>";
        echo "<p>Toutes les colonnes requises existent déjà dans la table.</p>";
        echo "</div>";
    }
    
    // ÉTAPE 4 : Vérification finale
    echo "<h2>Étape 4 : Vérification finale</h2>";
    $stmt = $pdo->query("DESCRIBE commandes_achats");
    $final_columns = $stmt->fetchAll();
    
    echo "<p><strong>Total colonnes après migration :</strong> " . count($final_columns) . "</p>";
    
    // Vérifier que toutes les colonnes requises sont présentes
    $final_column_names = array_column($final_columns, 'Field');
    $all_present = true;
    
    echo "<h3>Vérification des colonnes requises :</h3>";
    echo "<ul>";
    foreach ($colonnes_requises as $nom => $info) {
        $present = in_array($nom, $final_column_names);
        echo "<li>" . ($present ? "✅" : "❌") . " <strong>$nom</strong></li>";
        if (!$present) $all_present = false;
    }
    echo "</ul>";
    
    if ($all_present) {
        echo "<div class='success'>";
        echo "<h3>🎉 Toutes les colonnes sont présentes !</h3>";
        echo "<p>La table <code>commandes_achats</code> est maintenant prête.</p>";
        echo "<p><a href='dashboard.php' style='display:inline-block;padding:12px 24px;background:#007bff;color:white;text-decoration:none;border-radius:5px;font-weight:bold;'>→ Retour au Dashboard</a></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Erreur critique</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";

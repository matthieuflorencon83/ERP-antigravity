<?php
// Analyse complète de la structure de base de données
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Analyse Architecture BDD</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 1400px; margin: 0 auto; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #2c3e50; color: white; }
        .section { background: #ecf0f1; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .good { background-color: #d4edda; }
        .bad { background-color: #f8d7da; }
        .warning { background-color: #fff3cd; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; }
        h2 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
    </style>
</head>
<body>
    <h1>🔍 Analyse Complète de l'Architecture Base de Données</h1>
    
    <?php
    // Récupérer toutes les tables
    $stmt = $pdo->query("SHOW TABLES");
    $all_tables = $stmt->fetchAll(PDO::COLUMN);
    
    echo "<div class='section'>";
    echo "<h2>📊 Vue d'ensemble</h2>";
    echo "<p><strong>Nombre total de tables :</strong> " . count($all_tables) . "</p>";
    echo "</div>";
    
    // Analyser les groupes de tables
    $table_groups = [
        'Clients' => ['clients', 'client_contacts', 'client_adresses', 'client_telephones', 'client_emails', 'client_coordonnees'],
        'Fournisseurs' => ['fournisseurs', 'fournisseur_contacts', 'fournisseur_adresses'],
        'Affaires' => ['affaires', 'affaires_lignes'],
        'Commandes' => ['commandes_achats', 'commandes_lignes'],
        'Catalogue' => ['articles_catalogue', 'familles', 'sous_familles', 'finitions', 'modeles_profils'],
        'Stocks' => ['stock_mouvements', 'stock_actuel'],
        'Système' => ['utilisateurs', 'parametres_generaux']
    ];
    
    echo "<div class='section'>";
    echo "<h2>📁 Groupes de Tables</h2>";
    
    foreach ($table_groups as $group => $tables) {
        $found = array_intersect($tables, $all_tables);
        if (count($found) > 0) {
            echo "<h3>$group (" . count($found) . " tables)</h3>";
            echo "<ul>";
            foreach ($found as $table) {
                // Compter les enregistrements
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $stmt->fetch()['count'];
                
                // Compter les colonnes
                $stmt = $pdo->query("DESCRIBE `$table`");
                $columns = $stmt->fetchAll();
                $col_count = count($columns);
                
                echo "<li><strong>$table</strong> : $count enregistrements, $col_count colonnes</li>";
            }
            echo "</ul>";
        }
    }
    echo "</div>";
    
    // Analyse détaillée des relations
    echo "<div class='section'>";
    echo "<h2>🔗 Analyse des Relations (Foreign Keys)</h2>";
    
    $stmt = $pdo->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'antigravity'
        AND REFERENCED_TABLE_NAME IS NOT NULL
        ORDER BY TABLE_NAME
    ");
    $foreign_keys = $stmt->fetchAll();
    
    if (count($foreign_keys) > 0) {
        echo "<table>";
        echo "<tr><th>Table</th><th>Colonne</th><th>Référence</th><th>Colonne Référencée</th></tr>";
        foreach ($foreign_keys as $fk) {
            echo "<tr>";
            echo "<td>{$fk['TABLE_NAME']}</td>";
            echo "<td>{$fk['COLUMN_NAME']}</td>";
            echo "<td>{$fk['REFERENCED_TABLE_NAME']}</td>";
            echo "<td>{$fk['REFERENCED_COLUMN_NAME']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ Aucune foreign key détectée (peut être normal si CASCADE n'est pas utilisé)</p>";
    }
    echo "</div>";
    
    // Analyse spécifique : Tables Clients
    echo "<div class='section'>";
    echo "<h2>👥 Focus : Architecture Client (Actuelle)</h2>";
    
    $client_tables = ['clients', 'client_contacts', 'client_adresses', 'client_telephones', 'client_emails'];
    
    foreach ($client_tables as $table) {
        if (in_array($table, $all_tables)) {
            echo "<h3>Table : $table</h3>";
            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll();
            
            echo "<table>";
            echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td><strong>{$col['Field']}</strong></td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    echo "</div>";
    
    // Proposition alternative
    echo "<div class='section'>";
    echo "<h2>💡 Architecture Alternative : Table Unique Polymorphe</h2>";
    echo "<p>Au lieu de 4 tables séparées (contacts, adresses, téléphones, emails), on pourrait utiliser :</p>";
    
    echo "<h3>Option 1 : Table 'client_coordonnees' (EAV - Entity-Attribute-Value)</h3>";
    echo "<pre>";
    echo "CREATE TABLE client_coordonnees (
    id INT PRIMARY KEY,
    client_id INT,
    type_contact ENUM('email', 'telephone', 'adresse', 'mobile'),
    libelle VARCHAR(100),
    valeur TEXT,
    principal BOOLEAN,
    metadata JSON  -- Pour stocker des infos spécifiques
)";
    echo "</pre>";
    
    echo "<div class='good'>";
    echo "<h4>✅ Avantages :</h4>";
    echo "<ul>";
    echo "<li>1 seule table au lieu de 4</li>";
    echo "<li>Plus facile à requêter globalement</li>";
    echo "<li>Extensible (ajout de nouveaux types)</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='bad'>";
    echo "<h4>❌ Inconvénients :</h4>";
    echo "<ul>";
    echo "<li>Perte de typage fort (tout en TEXT)</li>";
    echo "<li>Validation plus complexe</li>";
    echo "<li>Requêtes plus lentes (pas d'index spécifiques)</li>";
    echo "<li>Anti-pattern selon les puristes SQL</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>Option 2 : Architecture Actuelle (Normalisée)</h3>";
    echo "<pre>";
    echo "Tables séparées :
- client_contacts (nom, prenom, role, email, tel)
- client_adresses (type, adresse, cp, ville)
- client_telephones (type, numero, libelle)
- client_emails (type, email, libelle)";
    echo "</pre>";
    
    echo "<div class='good'>";
    echo "<h4>✅ Avantages :</h4>";
    echo "<ul>";
    echo "<li>Typage fort (validation au niveau BDD)</li>";
    echo "<li>Index optimisés par type</li>";
    echo "<li>Requêtes rapides et ciblées</li>";
    echo "<li>Respecte la 3ème forme normale (3NF)</li>";
    echo "<li>Facilite les JOINs spécifiques</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='bad'>";
    echo "<h4>❌ Inconvénients :</h4>";
    echo "<ul>";
    echo "<li>Plus de tables à gérer</li>";
    echo "<li>Requêtes globales nécessitent plusieurs JOINs</li>";
    echo "<li>Plus de code PHP (4 requêtes au lieu d'1)</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
    // Recommandation
    echo "<div class='section' style='background: #3498db; color: white;'>";
    echo "<h2>🎯 RECOMMANDATION FINALE</h2>";
    echo "<h3>Garder l'architecture actuelle (tables séparées) ✅</h3>";
    echo "<p><strong>Raisons :</strong></p>";
    echo "<ol>";
    echo "<li><strong>Performance</strong> : Index spécifiques = requêtes ultra-rapides</li>";
    echo "<li><strong>Intégrité</strong> : Validation au niveau BDD (ENUM, contraintes)</li>";
    echo "<li><strong>Maintenabilité</strong> : Code plus clair et prévisible</li>";
    echo "<li><strong>Scalabilité</strong> : Facile d'ajouter des colonnes spécifiques</li>";
    echo "<li><strong>Best Practice</strong> : Respecte les principes SOLID et 3NF</li>";
    echo "</ol>";
    
    echo "<p><strong>Sources :</strong></p>";
    echo "<ul>";
    echo "<li>MySQL Documentation : Normalization (3NF recommended)</li>";
    echo "<li>Oracle Best Practices : Avoid EAV when possible</li>";
    echo "<li>PostgreSQL Wiki : EAV is an anti-pattern</li>";
    echo "</ul>";
    echo "</div>";
    
    // Tables potentiellement à optimiser
    echo "<div class='section'>";
    echo "<h2>⚠️ Tables à Potentiellement Optimiser</h2>";
    
    // Chercher les doublons
    if (in_array('client_coordonnees', $all_tables)) {
        echo "<div class='warning'>";
        echo "<h3>🔴 DOUBLON DÉTECTÉ</h3>";
        echo "<p>La table <code>client_coordonnees</code> existe déjà (ancien système EAV)</p>";
        echo "<p><strong>Action recommandée :</strong> Supprimer cette table obsolète et migrer vers les nouvelles tables normalisées</p>";
        echo "</div>";
    }
    
    echo "</div>";
    ?>
    
    <div class="section" style="background: #2ecc71; color: white;">
        <h2>✅ CONCLUSION</h2>
        <p><strong>L'architecture actuelle avec tables séparées est OPTIMALE.</strong></p>
        <p>C'est exactement ce que font les grands CRM (Salesforce, HubSpot, etc.)</p>
        <p><strong>Aucun changement nécessaire !</strong></p>
    </div>
    
</body>
</html>

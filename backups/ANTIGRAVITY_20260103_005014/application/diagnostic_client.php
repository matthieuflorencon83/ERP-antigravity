<?php
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnostic Client CRM</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 Diagnostic Module Client CRM</h1>
    
    <h2>1. Vérification des Tables</h2>
    <table>
        <tr>
            <th>Table</th>
            <th>Statut</th>
            <th>Colonnes</th>
            <th>Détails</th>
        </tr>
        <?php
        $tables = [
            'clients' => 'Table principale (modifiée)',
            'client_contacts' => 'Contacts secondaires',
            'client_adresses' => 'Adresses multiples',
            'client_telephones' => 'Téléphones multiples',
            'client_emails' => 'Emails multiples'
        ];
        
        foreach ($tables as $table => $description) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->query("DESCRIBE $table");
                $columns = $stmt->fetchAll();
                $colCount = count($columns);
                echo "<tr>";
                echo "<td><strong>$table</strong><br><small>$description</small></td>";
                echo "<td class='success'>✅ Existe</td>";
                echo "<td>$colCount colonnes</td>";
                echo "<td><small>";
                foreach ($columns as $col) {
                    echo $col['Field'] . ", ";
                }
                echo "</small></td>";
                echo "</tr>";
            } else {
                echo "<tr>";
                echo "<td><strong>$table</strong><br><small>$description</small></td>";
                echo "<td class='error'>❌ MANQUANTE</td>";
                echo "<td>-</td>";
                echo "<td>Table non créée</td>";
                echo "</tr>";
            }
        }
        ?>
    </table>
    
    <h2>2. Vérification des Fichiers PHP</h2>
    <table>
        <tr>
            <th>Fichier</th>
            <th>Statut</th>
            <th>Taille</th>
        </tr>
        <?php
        $files = [
            'clients_liste.php' => 'Liste des clients',
            'clients_detail.php' => 'Fiche client détaillée',
            'client_actions.php' => 'Actions CRUD',
            'clients_nouveau.php' => 'Nouveau client (optionnel)'
        ];
        
        foreach ($files as $file => $desc) {
            $path = __DIR__ . '/' . $file;
            if (file_exists($path)) {
                $size = filesize($path);
                echo "<tr>";
                echo "<td><strong>$file</strong><br><small>$desc</small></td>";
                echo "<td class='success'>✅ Existe</td>";
                echo "<td>" . number_format($size) . " octets</td>";
                echo "</tr>";
            } else {
                echo "<tr>";
                echo "<td><strong>$file</strong><br><small>$desc</small></td>";
                echo "<td class='error'>❌ MANQUANT</td>";
                echo "<td>-</td>";
                echo "</tr>";
            }
        }
        ?>
    </table>
    
    <h2>3. Test de Connexion Base de Données</h2>
    <?php
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
        $result = $stmt->fetch();
        echo "<p class='success'>✅ Connexion OK - " . $result['count'] . " clients dans la base</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
    }
    ?>
    
    <h2>4. Actions</h2>
    <p>
        <a href="clients_liste.php" style="padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;">→ Aller à la liste des clients</a>

    </p>
    
    <hr>
    <h2>5. Ré-exécuter le Script SQL</h2>
    <p class='warning'>⚠️ Si des tables sont manquantes, cliquez ci-dessous :</p>
    <p>
        <!-- Lien supprimé car script de dev obsolète -->
        <span style="color: grey;">Contacter l'administrateur si des tables sont manquantes.</span>
    </p>
</body>
</html>

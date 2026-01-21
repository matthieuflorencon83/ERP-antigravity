<?php
require_once 'db.php';

echo "<h2>Vérification des Tables Client CRM</h2>";

$tables = ['clients', 'client_contacts', 'client_adresses', 'client_telephones', 'client_emails'];

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Table</th><th>Statut</th><th>Nombre de Colonnes</th></tr>";

foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll();
        echo "<tr><td><strong>$table</strong></td><td style='color:green'>✅ Existe</td><td>" . count($columns) . "</td></tr>";
    } else {
        echo "<tr><td><strong>$table</strong></td><td style='color:red'>❌ Manquante</td><td>-</td></tr>";
    }
}

echo "</table>";

echo "<h3>✅ Vérification terminée !</h3>";
echo "<p><a href='clients_liste.php'>→ Aller à la liste des clients</a></p>";

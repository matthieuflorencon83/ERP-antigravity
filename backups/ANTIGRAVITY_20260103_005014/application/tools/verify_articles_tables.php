<?php
// tools/verify_articles_tables.php
require_once __DIR__ . '/../db.php';

echo "<h3>🔍 Vérification Tables ARTICLES</h3>";

$stmt = $pdo->query("SHOW TABLES LIKE 'articles%'");
$articlesTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<table class='table table-sm'>";
echo "<tr><th>Table</th><th>Lignes</th><th>Statut</th></tr>";

foreach($articlesTables as $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    
    if($table == 'articles_catalogue') {
        echo "<tr class='table-warning'>";
        echo "<td><strong>$table</strong></td>";
        echo "<td>$count</td>";
        echo "<td>⚠️ DEVRAIT ÊTRE SUPPRIMÉE</td>";
        echo "</tr>";
    } else {
        echo "<tr class='table-success'>";
        echo "<td><strong>$table</strong></td>";
        echo "<td>$count</td>";
        echo "<td>✓ Active</td>";
        echo "</tr>";
    }
}

echo "</table>";

// Check if articles has the migrated columns
echo "<h4>Colonnes de 'articles'</h4>";
$stmt = $pdo->query("DESCRIBE articles");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

$migratedCols = ['fabricant_id', 'type_vente', 'conditionnement_qte', 'longueurs_possibles_json', 'poids_metre_lineaire', 'inertie_lx', 'articles_lies_json'];

echo "<ul>";
foreach($migratedCols as $col) {
    $exists = false;
    foreach($cols as $c) {
        if($c['Field'] == $col) {
            $exists = true;
            break;
        }
    }
    
    if($exists) {
        echo "<li>✓ <strong>$col</strong> (migrée)</li>";
    } else {
        echo "<li>❌ <strong>$col</strong> (manquante)</li>";
    }
}
echo "</ul>";

if(in_array('articles_catalogue', $articlesTables)) {
    echo "<div class='alert alert-warning'>";
    echo "<h5>⚠️ PROBLÈME DÉTECTÉ</h5>";
    echo "<p>La table <code>articles_catalogue</code> existe encore alors qu'elle devrait être supprimée.</p>";
    echo "<p><strong>Action:</strong> Supprimer maintenant</p>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='drop_catalogue' class='btn btn-danger'>Supprimer articles_catalogue</button>";
    echo "</form>";
    echo "</div>";
    
    if(isset($_POST['drop_catalogue'])) {
        $pdo->exec("DROP TABLE IF EXISTS articles_catalogue");
        echo "<div class='alert alert-success'>✓ Table supprimée</div>";
        echo "<meta http-equiv='refresh' content='1'>";
    }
}

<?php
// tools/code_integrity_check.php
require_once __DIR__ . '/../db.php';

echo "<h1>🔗 AUDIT CONFORMITÉ CODE vs BDD</h1>";

// ÉTAPE 1: Vérité BDD
$stmt = $pdo->query("SHOW TABLES");
$validTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h3>📡 Tables Valides: " . count($validTables) . "</h3>";

// ÉTAPE 2: Tables supprimées à détecter
$deletedTables = ['articles_catalogue', 'familles', 'devis', 'devis_details', 'dashboard_postits', 'email_templates', 'fabricants'];

// ÉTAPE 3: Colonnes supprimées
$deletedColumns = [
    'couleur_ral' => 'articles',
    'famille' => 'articles',
    'sous_famille' => 'articles'
];

// Scan fichiers PHP
$phpFiles = array_merge(
    glob(__DIR__ . '/../*.php'),
    glob(__DIR__ . '/../ajax/*.php'),
    glob(__DIR__ . '/../controllers/*.php')
);

$issues = [];

foreach($phpFiles as $file) {
    $content = file_get_contents($file);
    $basename = basename($file);
    
    // Check deleted tables
    foreach($deletedTables as $table) {
        if(stripos($content, $table) !== false) {
            $issues[] = [
                'file' => $basename,
                'type' => 'Table supprimée',
                'item' => $table
            ];
        }
    }
    
    // Check deleted columns
    foreach($deletedColumns as $col => $table) {
        if(preg_match("/'$col'|\"$col\"/", $content)) {
            $issues[] = [
                'file' => $basename,
                'type' => 'Colonne supprimée',
                'item' => "$table.$col"
            ];
        }
    }
}

// Affichage
if(count($issues) > 0) {
    echo "<h3>⚠️ Problèmes Détectés: " . count($issues) . "</h3>";
    echo "<table class='table table-sm table-bordered'>";
    echo "<tr><th>Fichier</th><th>Type</th><th>Élément</th></tr>";
    
    foreach($issues as $issue) {
        echo "<tr class='table-warning'>";
        echo "<td>{$issue['file']}</td>";
        echo "<td>{$issue['type']}</td>";
        echo "<td><code>{$issue['item']}</code></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='alert alert-danger'>";
    echo "<h4>⚠️ CORRECTIONS REQUISES</h4>";
    echo "<p>Ces fichiers référencent des éléments supprimés de la BDD.</p>";
    echo "</div>";
} else {
    echo "<div class='alert alert-success'>";
    echo "<h2>✅ CODE 100% CONFORME</h2>";
    echo "<p>Aucun membre fantôme détecté.</p>";
    echo "</div>";
}

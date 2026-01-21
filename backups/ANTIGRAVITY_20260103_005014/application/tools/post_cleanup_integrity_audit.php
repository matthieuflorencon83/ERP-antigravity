<?php
// tools/post_cleanup_integrity_audit.php
require_once __DIR__ . '/../db.php';

echo "<h1>🔗 AUDIT CONFORMITÉ POST-NETTOYAGE</h1>";

// ===== ÉTAPE 1: VÉRITÉ BDD =====
echo "<h2>📡 ÉTAPE 1: VÉRITÉ BDD (Référence)</h2>";

$stmt = $pdo->query("SHOW TABLES");
$validTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$tableStructure = [];
foreach($validTables as $table) {
    $stmt = $pdo->query("DESCRIBE `$table`");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $tableStructure[$table] = $cols;
}

echo "<p><strong>Tables valides:</strong> " . count($validTables) . "</p>";
echo "<details><summary>Voir la liste</summary><ul>";
foreach($validTables as $t) {
    echo "<li>$t (" . count($tableStructure[$t]) . " colonnes)</li>";
}
echo "</ul></details>";

// ===== ÉTAPE 2: SCAN REQUÊTES SQL =====
echo "<h2>🔍 ÉTAPE 2: SCAN REQUÊTES SQL (Backend)</h2>";

$phpFiles = array_merge(
    glob(__DIR__ . '/../*.php'),
    glob(__DIR__ . '/../controllers/*.php'),
    glob(__DIR__ . '/../ajax/*.php'),
    glob(__DIR__ . '/../api/*.php')
);

$brokenQueries = [];
$deletedTables = ['articles_catalogue', 'familles', 'devis', 'devis_details'];

foreach($phpFiles as $file) {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    
    foreach($lines as $lineNum => $line) {
        // Check for SQL queries
        if(preg_match('/(FROM|INTO|UPDATE|JOIN)\s+`?(\w+)`?/i', $line, $matches)) {
            $table = $matches[2];
            
            // Check if table was deleted
            if(in_array($table, $deletedTables)) {
                $brokenQueries[] = [
                    'file' => basename($file),
                    'line' => $lineNum + 1,
                    'code' => trim($line),
                    'table' => $table,
                    'issue' => 'Table supprimée'
                ];
            }
            // Check if table doesn't exist
            elseif(!in_array($table, $validTables) && $table != 'information_schema') {
                $brokenQueries[] = [
                    'file' => basename($file),
                    'line' => $lineNum + 1,
                    'code' => trim($line),
                    'table' => $table,
                    'issue' => 'Table inexistante'
                ];
            }
        }
    }
}

if(count($brokenQueries) > 0) {
    echo "<table class='table table-sm table-bordered'>";
    echo "<tr><th>Fichier</th><th>Ligne</th><th>Table</th><th>Problème</th></tr>";
    foreach($brokenQueries as $bq) {
        echo "<tr class='table-danger'>";
        echo "<td>{$bq['file']}</td>";
        echo "<td>{$bq['line']}</td>";
        echo "<td><code>{$bq['table']}</code></td>";
        echo "<td>❌ {$bq['issue']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='text-success'>✅ Aucune requête cassée détectée</p>";
}

// ===== ÉTAPE 3: SCAN COLONNES =====
echo "<h2>🗂️ ÉTAPE 3: SCAN COLONNES (Mapping)</h2>";

$brokenColumns = [];
$deletedColumns = [
    'articles' => ['couleur_ral', 'famille', 'sous_famille'],
];

foreach($phpFiles as $file) {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    
    foreach($lines as $lineNum => $line) {
        // Check for column references in PHP
        foreach($deletedColumns as $table => $cols) {
            foreach($cols as $col) {
                if(preg_match("/['\"]" . $col . "['\"]/", $line) || preg_match("/\\\$" . $col . "/", $line)) {
                    $brokenColumns[] = [
                        'file' => basename($file),
                        'line' => $lineNum + 1,
                        'code' => trim(substr($line, 0, 100)),
                        'column' => $col,
                        'table' => $table
                    ];
                }
            }
        }
    }
}

if(count($brokenColumns) > 0) {
    echo "<table class='table table-sm table-bordered'>";
    echo "<tr><th>Fichier</th><th>Ligne</th><th>Colonne</th><th>Table</th></tr>";
    foreach($brokenColumns as $bc) {
        echo "<tr class='table-warning'>";
        echo "<td>{$bc['file']}</td>";
        echo "<td>{$bc['line']}</td>";
        echo "<td><code>{$bc['column']}</code></td>";
        echo "<td>{$bc['table']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='text-success'>✅ Aucune colonne obsolète référencée</p>";
}

// ===== RÉSUMÉ =====
echo "<hr><div class='alert alert-" . (count($brokenQueries) + count($brokenColumns) > 0 ? 'danger' : 'success') . "'>";
echo "<h3>📊 RÉSUMÉ AUDIT</h3>";
echo "<ul>";
echo "<li>Requêtes cassées: <strong>" . count($brokenQueries) . "</strong></li>";
echo "<li>Colonnes obsolètes: <strong>" . count($brokenColumns) . "</strong></li>";
echo "</ul>";

if(count($brokenQueries) + count($brokenColumns) == 0) {
    echo "<h2>✅ CODE 100% CONFORME</h2>";
    echo "<p>Aucun membre fantôme détecté. Le code est aligné avec la nouvelle BDD.</p>";
} else {
    echo "<h2>⚠️ CORRECTIONS REQUISES</h2>";
    echo "<p>Des fichiers référencent encore des tables/colonnes supprimées.</p>";
}
echo "</div>";

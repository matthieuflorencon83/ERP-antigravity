<?php
// tools/final_database_audit.php
require_once __DIR__ . '/../db.php';

echo "<h1>🔍 AUDIT FINAL - BASE DE DONNÉES</h1>";

// Get all tables
$stmt = $pdo->query("SHOW TABLES");
$allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>📊 ANALYSE COMPLÈTE</h2>";
echo "<p><strong>Tables actives:</strong> " . count($allTables) . "</p>";

// ===== CHECK 1: REDUNDANT COLUMNS =====
echo "<h3>1️⃣ COLONNES REDONDANTES</h3>";

$redundantColumns = [];

foreach($allTables as $table) {
    $stmt = $pdo->query("DESCRIBE `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $colNames = array_column($columns, 'Field');
    
    // Check for both text and FK versions
    $checks = [
        ['text' => 'famille', 'fk' => 'famille_id'],
        ['text' => 'sous_famille', 'fk' => 'sous_famille_id'],
        ['text' => 'couleur_ral', 'fk' => 'finition_id'],
        ['text' => 'fournisseur', 'fk' => 'fournisseur_id'],
        ['text' => 'article', 'fk' => 'article_id'],
    ];
    
    foreach($checks as $check) {
        if(in_array($check['text'], $colNames) && in_array($check['fk'], $colNames)) {
            $redundantColumns[] = [
                'table' => $table,
                'text_col' => $check['text'],
                'fk_col' => $check['fk'],
                'issue' => 'Doublon texte + FK'
            ];
        }
    }
}

if(count($redundantColumns) > 0) {
    echo "<table class='table table-sm table-bordered'>";
    echo "<tr><th>Table</th><th>Colonne Texte</th><th>Colonne FK</th><th>Action</th></tr>";
    foreach($redundantColumns as $rc) {
        echo "<tr class='table-warning'>";
        echo "<td>{$rc['table']}</td>";
        echo "<td>{$rc['text_col']}</td>";
        echo "<td>{$rc['fk_col']}</td>";
        echo "<td>DROP COLUMN `{$rc['text_col']}`</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='text-success'>✅ Aucune colonne redondante détectée</p>";
}

// ===== CHECK 2: OBSOLETE COLUMNS =====
echo "<h3>2️⃣ COLONNES OBSOLÈTES</h3>";

$obsoletePatterns = ['_old', '_backup', '_temp', '_deprecated', '_legacy'];
$obsoleteColumns = [];

foreach($allTables as $table) {
    $stmt = $pdo->query("DESCRIBE `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($columns as $col) {
        foreach($obsoletePatterns as $pattern) {
            if(stripos($col['Field'], $pattern) !== false) {
                $obsoleteColumns[] = [
                    'table' => $table,
                    'column' => $col['Field'],
                    'type' => $col['Type']
                ];
            }
        }
    }
}

if(count($obsoleteColumns) > 0) {
    echo "<table class='table table-sm table-bordered'>";
    echo "<tr><th>Table</th><th>Colonne</th><th>Type</th><th>Action</th></tr>";
    foreach($obsoleteColumns as $oc) {
        echo "<tr class='table-warning'>";
        echo "<td>{$oc['table']}</td>";
        echo "<td>{$oc['column']}</td>";
        echo "<td>{$oc['type']}</td>";
        echo "<td>DROP COLUMN `{$oc['column']}`</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='text-success'>✅ Aucune colonne obsolète détectée</p>";
}

// ===== CHECK 3: MISSING INDEXES =====
echo "<h3>3️⃣ INDEX MANQUANTS</h3>";

$missingIndexes = [];

foreach($allTables as $table) {
    $stmt = $pdo->query("DESCRIBE `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get existing indexes
    $stmt = $pdo->query("SHOW INDEX FROM `$table`");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $indexedCols = array_column($indexes, 'Column_name');
    
    foreach($columns as $col) {
        $colName = $col['Field'];
        
        // FK columns should have index
        if(preg_match('/_id$/', $colName) && $colName != 'id' && !in_array($colName, $indexedCols)) {
            $missingIndexes[] = [
                'table' => $table,
                'column' => $colName
            ];
        }
    }
}

if(count($missingIndexes) > 0) {
    echo "<table class='table table-sm table-bordered'>";
    echo "<tr><th>Table</th><th>Colonne</th><th>Action</th></tr>";
    foreach($missingIndexes as $mi) {
        echo "<tr class='table-info'>";
        echo "<td>{$mi['table']}</td>";
        echo "<td>{$mi['column']}</td>";
        echo "<td>CREATE INDEX idx_{$mi['column']} ON `{$mi['table']}`(`{$mi['column']}`)</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='text-success'>✅ Tous les index nécessaires sont présents</p>";
}

// ===== CHECK 4: DUPLICATE TABLES =====
echo "<h3>4️⃣ TABLES DUPLIQUÉES</h3>";

$duplicateTables = [];
$baseTables = [];

foreach($allTables as $table) {
    $base = preg_replace('/(s|_articles|_catalogue|_details)$/', '', $table);
    
    if(isset($baseTables[$base])) {
        $baseTables[$base][] = $table;
    } else {
        $baseTables[$base] = [$table];
    }
}

foreach($baseTables as $base => $tables) {
    if(count($tables) > 1) {
        $duplicateTables[] = [
            'base' => $base,
            'tables' => $tables
        ];
    }
}

if(count($duplicateTables) > 0) {
    echo "<table class='table table-sm table-bordered'>";
    echo "<tr><th>Base</th><th>Tables</th><th>Statut</th></tr>";
    foreach($duplicateTables as $dt) {
        echo "<tr class='table-warning'>";
        echo "<td>{$dt['base']}</td>";
        echo "<td>" . implode(', ', $dt['tables']) . "</td>";
        echo "<td>Vérifier si doublon</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='text-success'>✅ Aucune table dupliquée détectée</p>";
}

// ===== SUMMARY =====
echo "<hr><div class='alert alert-" . (count($redundantColumns) + count($obsoleteColumns) > 0 ? 'warning' : 'success') . "'>";
echo "<h3>📊 RÉSUMÉ AUDIT</h3>";
echo "<ul>";
echo "<li>Colonnes redondantes: <strong>" . count($redundantColumns) . "</strong></li>";
echo "<li>Colonnes obsolètes: <strong>" . count($obsoleteColumns) . "</strong></li>";
echo "<li>Index manquants: <strong>" . count($missingIndexes) . "</strong></li>";
echo "<li>Tables dupliquées: <strong>" . count($duplicateTables) . "</strong></li>";
echo "</ul>";

$score = 10;
$score -= count($redundantColumns) * 1;
$score -= count($obsoleteColumns) * 0.5;
$score -= count($missingIndexes) * 0.3;
$score -= count($duplicateTables) * 1;
$score = max(0, $score);

echo "<h2>Score Propreté: " . round($score, 1) . "/10</h2>";
echo "</div>";

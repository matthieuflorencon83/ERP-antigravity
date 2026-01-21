<?php
// tools/big_bang_deep_core_audit.php
require_once __DIR__ . '/../db.php';

echo "<h1>☢️ BIG BANG AUDIT - DEEP CORE & SIMPLIFICATION</h1>";

// ===== PHASE 1: SCAN DE LA RÉALITÉ =====
echo "<h2>📡 PHASE 1: SCAN DE LA RÉALITÉ</h2>";

// Get all tables
$stmt = $pdo->query("SHOW TABLES");
$allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Scan PHP files
$phpFiles = array_merge(
    glob(__DIR__ . '/../*.php'),
    glob(__DIR__ . '/../controllers/*.php'),
    glob(__DIR__ . '/../models/*.php'),
    glob(__DIR__ . '/../ajax/*.php')
);

$jsFiles = glob(__DIR__ . '/../assets/js/**/*.js');

echo "<p><strong>Tables BDD:</strong> " . count($allTables) . "</p>";
echo "<p><strong>Fichiers PHP:</strong> " . count($phpFiles) . "</p>";
echo "<p><strong>Fichiers JS:</strong> " . count($jsFiles) . "</p>";

// ===== PHASE 2: FILTRE A - ABSORPTION JSON =====
echo "<h2>📉 FILTRE A: ABSORPTION JSON (Tables → JSON)</h2>";

$jsonCandidates = [];

// Detect tables that look like "detail" or "options" tables
foreach($allTables as $table) {
    // Get table structure
    $stmt = $pdo->query("DESCRIBE `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $colNames = array_column($columns, 'Field');
    
    // Check if table has parent_id pattern
    $hasParentId = false;
    $parentTable = null;
    
    foreach($colNames as $col) {
        if(preg_match('/^(.+)_id$/', $col, $matches) && $matches[1] != 'id') {
            $potentialParent = $matches[1];
            if(in_array($potentialParent, $allTables) || in_array($potentialParent . 's', $allTables)) {
                $hasParentId = true;
                $parentTable = in_array($potentialParent, $allTables) ? $potentialParent : $potentialParent . 's';
            }
        }
    }
    
    // Patterns suggesting JSON absorption
    $isDetailTable = (
        stripos($table, '_details') !== false ||
        stripos($table, '_options') !== false ||
        stripos($table, '_vitrages') !== false ||
        stripos($table, '_accessoires') !== false ||
        stripos($table, '_lignes_details') !== false
    );
    
    if($isDetailTable && $hasParentId) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        $jsonCandidates[] = [
            'table' => $table,
            'parent' => $parentTable,
            'rows' => $count,
            'columns' => count($colNames)
        ];
    }
}

echo "<table class='table table-sm table-bordered'>";
echo "<tr><th>Table Actuelle</th><th>Table Cible</th><th>Lignes</th><th>Action</th><th>Gain Technique</th></tr>";

foreach($jsonCandidates as $jc) {
    echo "<tr class='table-warning'>";
    echo "<td><strong>{$jc['table']}</strong></td>";
    echo "<td>{$jc['parent']}</td>";
    echo "<td>{$jc['rows']}</td>";
    echo "<td>🗑️ DELETE & MOVE TO JSON</td>";
    echo "<td>Évite 1 JOIN. Stockage dans data_json</td>";
    echo "</tr>";
}

if(count($jsonCandidates) == 0) {
    echo "<tr><td colspan='5' class='text-success'>✅ Aucune table candidate pour JSON</td></tr>";
}

echo "</table>";

// ===== FILTRE B: FUSION 1-POUR-1 =====
echo "<h2>🔗 FILTRE B: FUSION 1-POUR-1 (Tables Redondantes)</h2>";

$fusionCandidates = [];

// Detect 1-to-1 relationships
foreach($allTables as $table) {
    // Check for tables with same prefix
    $baseName = preg_replace('/(s|_details|_info|_data)$/', '', $table);
    
    foreach($allTables as $otherTable) {
        if($table != $otherTable && stripos($otherTable, $baseName) === 0) {
            // Check if 1-to-1
            $stmt = $pdo->query("DESCRIBE `$otherTable`");
            $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if(in_array($table . '_id', $cols) || in_array(rtrim($table, 's') . '_id', $cols)) {
                $count1 = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                $count2 = $pdo->query("SELECT COUNT(*) FROM `$otherTable`")->fetchColumn();
                
                // If counts similar, likely 1-to-1
                if(abs($count1 - $count2) < 5 && $count1 > 0) {
                    $fusionCandidates[] = [
                        'table1' => $table,
                        'table2' => $otherTable,
                        'count1' => $count1,
                        'count2' => $count2
                    ];
                }
            }
        }
    }
}

echo "<table class='table table-sm table-bordered'>";
echo "<tr><th>Table 1</th><th>Table 2</th><th>Lignes</th><th>Action</th><th>Gain</th></tr>";

foreach($fusionCandidates as $fc) {
    echo "<tr class='table-info'>";
    echo "<td>{$fc['table1']}</td>";
    echo "<td>{$fc['table2']}</td>";
    echo "<td>{$fc['count1']} / {$fc['count2']}</td>";
    echo "<td>🔀 FUSIONNER</td>";
    echo "<td>Moins de JOIN = Moins de bugs</td>";
    echo "</tr>";
}

if(count($fusionCandidates) == 0) {
    echo "<tr><td colspan='5' class='text-success'>✅ Aucune fusion 1-to-1 détectée</td></tr>";
}

echo "</table>";

// ===== FILTRE C: FOREIGN KEYS MANQUANTES =====
echo "<h2>🔗 FILTRE C: FOREIGN KEYS MANQUANTES</h2>";

// Get existing FKs
$stmt = $pdo->query("
    SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'antigravity' AND REFERENCED_TABLE_NAME IS NOT NULL
");
$existingFKs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$missingFKs = [];

foreach($allTables as $table) {
    $stmt = $pdo->query("DESCRIBE `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($columns as $col) {
        $colName = $col['Field'];
        
        // Check if column looks like FK (ends with _id)
        if(preg_match('/^(.+)_id$/', $colName, $matches) && $colName != 'id') {
            $refTable = $matches[1];
            $refTablePlural = $refTable . 's';
            
            // Check if referenced table exists
            if(in_array($refTable, $allTables) || in_array($refTablePlural, $allTables)) {
                $actualRefTable = in_array($refTable, $allTables) ? $refTable : $refTablePlural;
                
                // Check if FK exists
                $fkExists = false;
                foreach($existingFKs as $fk) {
                    if($fk['TABLE_NAME'] == $table && $fk['COLUMN_NAME'] == $colName) {
                        $fkExists = true;
                        break;
                    }
                }
                
                if(!$fkExists) {
                    $missingFKs[] = [
                        'table' => $table,
                        'column' => $colName,
                        'ref_table' => $actualRefTable
                    ];
                }
            }
        }
    }
}

echo "<table class='table table-sm table-bordered'>";
echo "<tr><th>Table</th><th>Colonne</th><th>Référence</th><th>Risque</th><th>Correction</th></tr>";

foreach($missingFKs as $mfk) {
    $sql = "ALTER TABLE `{$mfk['table']}` ADD FOREIGN KEY (`{$mfk['column']}`) REFERENCES `{$mfk['ref_table']}`(id);";
    echo "<tr class='table-danger'>";
    echo "<td>{$mfk['table']}</td>";
    echo "<td>{$mfk['column']}</td>";
    echo "<td>❌ {$mfk['ref_table']}</td>";
    echo "<td>Orphelins possibles</td>";
    echo "<td><code>$sql</code></td>";
    echo "</tr>";
}

if(count($missingFKs) == 0) {
    echo "<tr><td colspan='5' class='text-success'>✅ Toutes les FKs sont déclarées</td></tr>";
}

echo "</table>";

// ===== RÉSUMÉ =====
echo "<hr><div class='alert alert-warning'>";
echo "<h3>📊 RÉSUMÉ SIMPLIFICATION</h3>";
echo "<p><strong>Tables JSON:</strong> " . count($jsonCandidates) . " candidates</p>";
echo "<p><strong>Fusions 1-to-1:</strong> " . count($fusionCandidates) . " candidates</p>";
echo "<p><strong>FKs manquantes:</strong> " . count($missingFKs) . "</p>";
echo "<p><strong>Gain potentiel:</strong> -" . (count($jsonCandidates) + count($fusionCandidates)) . " tables</p>";
echo "</div>";

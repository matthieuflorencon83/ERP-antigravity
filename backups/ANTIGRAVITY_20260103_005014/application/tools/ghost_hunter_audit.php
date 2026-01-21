<?php
// tools/ghost_hunter_audit.php
require_once __DIR__ . '/../db.php';

echo "<h2>🕵️‍♂️ GHOST HUNTER - AUDIT SYSTÉMIQUE</h2>";
echo "<p><em>Détection: Tables Orphelines, Fonctions Fantômes, Code Zombie</em></p>";

// AXE 1: TABLES ORPHELINES
echo "<h3>📊 AXE 1: TABLES ORPHELINES (BDD vs CODE)</h3>";

$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$phpFiles = glob(__DIR__ . '/../*.php');
$phpFiles = array_merge($phpFiles, glob(__DIR__ . '/../controllers/*.php'));
$phpFiles = array_merge($phpFiles, glob(__DIR__ . '/../ajax/*.php'));

$orphanedTables = [];
$activeTablesCount = 0;

foreach($tables as $table) {
    $found = false;
    $references = 0;
    
    foreach($phpFiles as $file) {
        $content = file_get_contents($file);
        if(stripos($content, $table) !== false) {
            $found = true;
            // Count actual SQL usage
            if(preg_match_all("/FROM\s+$table|INTO\s+$table|UPDATE\s+$table/i", $content, $matches)) {
                $references += count($matches[0]);
            }
        }
    }
    
    if(!$found) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        $orphanedTables[] = ['table' => $table, 'rows' => $count];
    } else {
        $activeTablesCount++;
    }
}

echo "<table class='table table-sm'>";
echo "<tr><th>Table</th><th>Lignes</th><th>Statut</th><th>Action</th></tr>";
foreach($orphanedTables as $t) {
    $status = $t['rows'] > 0 ? "⚠️ DONNÉES PERDUES" : "👻 FANTÔME";
    $action = $t['rows'] > 0 ? "Créer Controller OU Migrer données" : "Supprimer";
    echo "<tr class='table-warning'><td>{$t['table']}</td><td>{$t['rows']}</td><td>$status</td><td>$action</td></tr>";
}
echo "</table>";
echo "<p><strong>Résumé:</strong> $activeTablesCount tables actives, " . count($orphanedTables) . " orphelines</p>";

// AXE 2: FONCTIONS FANTÔMES
echo "<h3>🧠 AXE 2: FONCTIONS FANTÔMES (Backend vs Frontend)</h3>";

$phantomFunctions = [];
$criticalFiles = ['gestion_metrage.php', 'commandes_liste.php', 'besoins_saisie_v2.php'];

foreach($criticalFiles as $file) {
    $path = __DIR__ . '/../' . $file;
    if(file_exists($path)) {
        $content = file_get_contents($path);
        // Detect complex functions
        preg_match_all('/function\s+(\w+)\s*\(/', $content, $matches);
        if(!empty($matches[1])) {
            foreach($matches[1] as $func) {
                // Check if function result is displayed
                if(stripos($content, "echo") === false || stripos($content, $func . "(") === false) {
                    $phantomFunctions[] = ['file' => $file, 'function' => $func];
                }
            }
        }
    }
}

echo "<table class='table table-sm'>";
echo "<tr><th>Fichier</th><th>Fonction</th><th>Problème</th></tr>";
foreach($phantomFunctions as $pf) {
    echo "<tr class='table-info'><td>{$pf['file']}</td><td>{$pf['function']}()</td><td>⚠️ Codée mais résultat non affiché</td></tr>";
}
echo "</table>";

// AXE 3: INTÉGRITÉ RELATIONNELLE
echo "<h3>🔗 AXE 3: INTÉGRITÉ RELATIONNELLE (Foreign Keys)</h3>";

$stmt = $pdo->query("
    SELECT 
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'antigravity' 
    AND REFERENCED_TABLE_NAME IS NOT NULL
");
$existingFKs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check critical tables
$criticalRelations = [
    ['table' => 'articles', 'column' => 'fournisseur_prefere_id', 'ref_table' => 'fournisseurs'],
    ['table' => 'commandes_achats', 'column' => 'affaire_id', 'ref_table' => 'affaires'],
    ['table' => 'commandes_achats', 'column' => 'fournisseur_id', 'ref_table' => 'fournisseurs'],
];

$missingFKs = [];
foreach($criticalRelations as $rel) {
    $found = false;
    foreach($existingFKs as $fk) {
        if($fk['TABLE_NAME'] == $rel['table'] && $fk['COLUMN_NAME'] == $rel['column']) {
            $found = true;
            break;
        }
    }
    if(!$found) {
        // Check if column exists
        try {
            $stmt = $pdo->query("DESCRIBE {$rel['table']}");
            $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if(in_array($rel['column'], $cols)) {
                $missingFKs[] = $rel;
            }
        } catch(PDOException $e) {}
    }
}

echo "<table class='table table-sm'>";
echo "<tr><th>Table</th><th>Colonne</th><th>Référence</th><th>Script SQL</th></tr>";
foreach($missingFKs as $mfk) {
    $sql = "ALTER TABLE {$mfk['table']} ADD FOREIGN KEY ({$mfk['column']}) REFERENCES {$mfk['ref_table']}(id);";
    echo "<tr class='table-danger'><td>{$mfk['table']}</td><td>{$mfk['column']}</td><td>❌ {$mfk['ref_table']}</td><td><code>$sql</code></td></tr>";
}
echo "</table>";

// AXE 4: CODE ZOMBIE
echo "<h3>🧟 AXE 4: CODE ZOMBIE (Fichiers obsolètes)</h3>";

$zombiePatterns = ['_old', '_v1', '_backup', '_temp', 'test_'];
$zombieFiles = [];

foreach($phpFiles as $file) {
    $basename = basename($file);
    foreach($zombiePatterns as $pattern) {
        if(stripos($basename, $pattern) !== false) {
            $zombieFiles[] = $basename;
            break;
        }
    }
}

echo "<ul>";
foreach($zombieFiles as $zf) {
    echo "<li>⚠️ <code>$zf</code> - Fichier suspect (pattern zombie détecté)</li>";
}
echo "</ul>";

// CONCLUSION
echo "<hr><h3>🎯 TOP 3 ACTIONS PRIORITAIRES</h3>";
echo "<ol>";
echo "<li><strong>Supprimer " . count($orphanedTables) . " tables orphelines</strong> (ou créer les controllers manquants)</li>";
echo "<li><strong>Ajouter " . count($missingFKs) . " Foreign Keys critiques</strong> (intégrité données)</li>";
echo "<li><strong>Nettoyer " . count($zombieFiles) . " fichiers zombies</strong> (clarté codebase)</li>";
echo "</ol>";

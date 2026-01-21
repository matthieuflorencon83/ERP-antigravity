<?php
// tools/ghost_hunter_v2_complete_audit.php
require_once __DIR__ . '/../db.php';

echo "<h1>🕵️‍♂️ GHOST HUNTER V2 - AUDIT FORENSIQUE COMPLET</h1>";
echo "<p><em>Analyse Post-Nettoyage : 4 Axes d'Investigation</em></p>";

// ===== AXE 1: TABLES ORPHELINES =====
echo "<h2>📊 AXE 1: TABLES ORPHELINES (BDD vs CODE)</h2>";

$stmt = $pdo->query("SHOW TABLES");
$allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$phpFiles = array_merge(
    glob(__DIR__ . '/../*.php'),
    glob(__DIR__ . '/../controllers/*.php'),
    glob(__DIR__ . '/../ajax/*.php'),
    glob(__DIR__ . '/../views/**/*.php')
);

$orphanedTables = [];
$activeTables = [];

foreach($allTables as $table) {
    $found = false;
    $usageCount = 0;
    
    foreach($phpFiles as $file) {
        $content = file_get_contents($file);
        if(preg_match_all("/FROM\s+`?$table`?|INTO\s+`?$table`?|UPDATE\s+`?$table`?|TABLE\s+`?$table`?/i", $content, $matches)) {
            $found = true;
            $usageCount += count($matches[0]);
        }
    }
    
    if(!$found) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        $orphanedTables[] = ['table' => $table, 'rows' => $count];
    } else {
        $activeTables[] = ['table' => $table, 'usage' => $usageCount];
    }
}

echo "<h3>👻 Tables Orphelines: " . count($orphanedTables) . "</h3>";
echo "<table class='table table-sm table-bordered'>";
echo "<tr><th>Table</th><th>Lignes</th><th>Statut</th><th>Action Requise</th></tr>";
foreach($orphanedTables as $t) {
    $status = $t['rows'] > 0 ? "⚠️ DONNÉES" : "👻 VIDE";
    $action = $t['rows'] > 0 ? "Analyser usage métier OU Migrer" : "DROP TABLE `{$t['table']}`;";
    echo "<tr><td><strong>{$t['table']}</strong></td><td>{$t['rows']}</td><td>$status</td><td><code>$action</code></td></tr>";
}
echo "</table>";

// ===== AXE 2: FONCTIONS FANTÔMES =====
echo "<h2>🧠 AXE 2: FONCTIONS FANTÔMES (Backend vs Frontend)</h2>";

$criticalFiles = [
    'gestion_metrage.php',
    'commandes_liste.php',
    'besoins_saisie_v2.php',
    'gestion_commande_rapide.php'
];

$phantomFunctions = [];

foreach($criticalFiles as $file) {
    $path = __DIR__ . '/../' . $file;
    if(file_exists($path)) {
        $content = file_get_contents($path);
        
        // Detect PHP functions
        preg_match_all('/function\s+(\w+)\s*\([^)]*\)\s*{([^}]+)}/s', $content, $matches, PREG_SET_ORDER);
        
        foreach($matches as $match) {
            $funcName = $match[1];
            $funcBody = $match[2];
            
            // Check if function does calculation but result not displayed
            $hasCalculation = (stripos($funcBody, 'return') !== false || stripos($funcBody, '=') !== false);
            $isDisplayed = (stripos($content, "echo.*$funcName") !== false || stripos($content, "print.*$funcName") !== false);
            
            // Check if called in JS
            $jsFiles = glob(__DIR__ . '/../assets/js/**/*.js');
            $calledInJS = false;
            foreach($jsFiles as $jsFile) {
                if(stripos(file_get_contents($jsFile), $funcName) !== false) {
                    $calledInJS = true;
                    break;
                }
            }
            
            if($hasCalculation && !$isDisplayed && !$calledInJS && !in_array($funcName, ['__construct', 'init'])) {
                $phantomFunctions[] = [
                    'file' => $file,
                    'function' => $funcName,
                    'type' => 'Calcul non affiché'
                ];
            }
        }
    }
}

echo "<h3>⚠️ Fonctions Fantômes Détectées: " . count($phantomFunctions) . "</h3>";
if(count($phantomFunctions) > 0) {
    echo "<table class='table table-sm table-bordered'>";
    echo "<tr><th>Fichier</th><th>Fonction</th><th>Problème</th><th>Action Requise</th></tr>";
    foreach($phantomFunctions as $pf) {
        echo "<tr><td>{$pf['file']}</td><td><code>{$pf['function']}()</code></td><td>{$pf['type']}</td><td>Afficher résultat dans UI</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='text-success'>✅ Aucune fonction fantôme détectée</p>";
}

// ===== AXE 3: INTÉGRITÉ RELATIONNELLE =====
echo "<h2>🔗 AXE 3: INTÉGRITÉ RELATIONNELLE (Foreign Keys)</h2>";

// Get existing FKs
$stmt = $pdo->query("
    SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'antigravity' AND REFERENCED_TABLE_NAME IS NOT NULL
");
$existingFKs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check critical relations
$criticalRelations = [
    ['table' => 'commandes_achats', 'column' => 'affaire_id', 'ref' => 'affaires'],
    ['table' => 'commandes_achats', 'column' => 'fournisseur_id', 'ref' => 'fournisseurs'],
    ['table' => 'commandes_express', 'column' => 'affaire_id', 'ref' => 'affaires'],
    ['table' => 'articles', 'column' => 'famille_id', 'ref' => 'familles_articles'],
    ['table' => 'articles', 'column' => 'sous_famille_id', 'ref' => 'sous_familles_articles'],
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
            $stmt = $pdo->query("DESCRIBE `{$rel['table']}`");
            $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if(in_array($rel['column'], $cols)) {
                $missingFKs[] = $rel;
            }
        } catch(PDOException $e) {}
    }
}

echo "<h3>FKs Manquantes: " . count($missingFKs) . "</h3>";
if(count($missingFKs) > 0) {
    echo "<table class='table table-sm table-bordered'>";
    echo "<tr><th>Table</th><th>Colonne</th><th>Référence</th><th>Script SQL</th></tr>";
    foreach($missingFKs as $mfk) {
        $sql = "ALTER TABLE `{$mfk['table']}` ADD FOREIGN KEY (`{$mfk['column']}`) REFERENCES `{$mfk['ref']}`(id);";
        echo "<tr><td>{$mfk['table']}</td><td>{$mfk['column']}</td><td>❌ {$mfk['ref']}</td><td><code>$sql</code></td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='text-success'>✅ Toutes les Foreign Keys critiques sont en place</p>";
}

// ===== AXE 4: CODE MORT =====
echo "<h2>🧟 AXE 4: CODE MORT (Commentaires & Fichiers Obsolètes)</h2>";

$deadCode = [];

// Check for large commented blocks
foreach($phpFiles as $file) {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $commentedLines = 0;
    
    foreach($lines as $line) {
        if(preg_match('/^\s*(\/\/|#|\*)/', $line)) {
            $commentedLines++;
        }
    }
    
    $totalLines = count($lines);
    $commentRatio = $totalLines > 0 ? ($commentedLines / $totalLines) * 100 : 0;
    
    if($commentRatio > 30 && $totalLines > 50) {
        $deadCode[] = [
            'file' => basename($file),
            'ratio' => round($commentRatio, 1),
            'issue' => 'Trop de code commenté'
        ];
    }
}

echo "<h3>Code Mort Détecté: " . count($deadCode) . "</h3>";
if(count($deadCode) > 0) {
    echo "<table class='table table-sm table-bordered'>";
    echo "<tr><th>Fichier</th><th>% Commentaires</th><th>Action</th></tr>";
    foreach($deadCode as $dc) {
        echo "<tr><td>{$dc['file']}</td><td>{$dc['ratio']}%</td><td>Nettoyer commentaires obsolètes</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='text-success'>✅ Pas de code mort excessif détecté</p>";
}

// ===== CONCLUSION =====
echo "<hr><h2>🎯 CONCLUSION STRATÉGIQUE</h2>";
echo "<h3>Top 3 Actions Prioritaires pour Production:</h3>";
echo "<ol>";
if(count($orphanedTables) > 0) {
    echo "<li><strong>Nettoyer " . count($orphanedTables) . " tables orphelines</strong> (Clarté BDD)</li>";
}
if(count($missingFKs) > 0) {
    echo "<li><strong>Ajouter " . count($missingFKs) . " Foreign Keys</strong> (Intégrité données)</li>";
}
if(count($phantomFunctions) > 0) {
    echo "<li><strong>Activer " . count($phantomFunctions) . " fonctions dormantes</strong> (Valeur métier)</li>";
}
if(count($deadCode) > 0) {
    echo "<li><strong>Nettoyer code mort dans " . count($deadCode) . " fichiers</strong> (Maintenabilité)</li>";
}
echo "</ol>";

echo "<div class='alert alert-info'>";
echo "<strong>Score Santé Global:</strong> ";
$score = 10;
$score -= count($orphanedTables) * 0.5;
$score -= count($missingFKs) * 1;
$score -= count($phantomFunctions) * 0.3;
$score -= count($deadCode) * 0.2;
$score = max(0, $score);
echo round($score, 1) . "/10";
echo "</div>";

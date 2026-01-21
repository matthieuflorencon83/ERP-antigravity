<?php
// tools/analyze_catalog_schema.php
require_once __DIR__ . '/../db.php';

echo "=== ANALYSE COMPLÈTE DU SCHÉMA CATALOGUE ===\n\n";

$tables = ['articles', 'fabricants', 'familles', 'familles_articles', 'finitions', 'sous_familles_articles'];

foreach($tables as $table) {
    echo "--- TABLE: $table ---\n";
    try {
        // Schema
        $stmt = $pdo->query("DESCRIBE $table");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($cols as $c) {
            echo "  {$c['Field']} | {$c['Type']} | {$c['Key']}\n";
        }
        
        // Count
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "  → $count lignes\n\n";
    } catch(PDOException $e) {
        echo "  ✗ N'EXISTE PAS\n\n";
    }
}

echo "=== RELATIONS ===\n";
$stmt = $pdo->query("
    SELECT 
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'antigravity' 
    AND REFERENCED_TABLE_NAME IS NOT NULL
    AND TABLE_NAME IN ('articles', 'fabricants', 'familles_articles', 'sous_familles_articles', 'finitions')
    ORDER BY TABLE_NAME
");

foreach($stmt->fetchAll() as $fk) {
    echo "{$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
}

echo "\n=== PROBLÈMES DÉTECTÉS ===\n";

// Check 1: Duplicate famille tables
$familles = $pdo->query("SELECT COUNT(*) FROM familles")->fetchColumn();
$famillesArticles = $pdo->query("SELECT COUNT(*) FROM familles_articles")->fetchColumn();
if($familles > 0 && $famillesArticles > 0) {
    echo "⚠️ REDONDANCE: 'familles' ($familles) et 'familles_articles' ($famillesArticles) existent\n";
}

// Check 2: Articles using text famille vs ID
$stmt = $pdo->query("DESCRIBE articles");
$hasTextFamille = false;
$hasFamilleId = false;
foreach($stmt->fetchAll() as $c) {
    if($c['Field'] == 'famille' && strpos($c['Type'], 'varchar') !== false) $hasTextFamille = true;
    if($c['Field'] == 'famille_id') $hasFamilleId = true;
}
if($hasTextFamille && !$hasFamilleId) {
    echo "⚠️ STRUCTURE: 'articles.famille' est en texte (devrait être une FK vers familles_articles)\n";
}

// Check 3: Fabricants usage
$fabricantsCount = $pdo->query("SELECT COUNT(*) FROM fabricants")->fetchColumn();
$articlesWithFabricant = $pdo->query("SELECT COUNT(*) FROM articles WHERE fabricant_id IS NOT NULL")->fetchColumn();
echo "\n📊 Fabricants: $fabricantsCount dans table, $articlesWithFabricant articles liés\n";

echo "\n=== RECOMMANDATIONS ===\n";
echo "1. Fusionner 'familles' et 'familles_articles' (si doublon)\n";
echo "2. Remplacer 'articles.famille' (texte) par 'famille_id' (FK)\n";
echo "3. Remplacer 'articles.sous_famille' (texte) par FK existante\n";
echo "4. Vérifier utilisation réelle de 'fabricants'\n";

<?php
// tools/compare_articles_tables.php
require_once __DIR__ . '/../db.php';

echo "=== TABLE: articles ===\n";
$stmt = $pdo->query("DESCRIBE articles");
$articlesSchema = [];
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    $articlesSchema[] = $col['Field'];
    echo "  {$col['Field']} ({$col['Type']})\n";
}

echo "\n=== TABLE: articles_catalogue ===\n";
$stmt = $pdo->query("DESCRIBE articles_catalogue");
$catalogueSchema = [];
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    $catalogueSchema[] = $col['Field'];
    echo "  {$col['Field']} ({$col['Type']})\n";
}

echo "\n=== COMPARAISON ===\n";
echo "Colonnes UNIQUEMENT dans 'articles':\n";
$onlyArticles = array_diff($articlesSchema, $catalogueSchema);
foreach($onlyArticles as $col) echo "  - $col\n";

echo "\nColonnes UNIQUEMENT dans 'articles_catalogue':\n";
$onlyCatalogue = array_diff($catalogueSchema, $articlesSchema);
foreach($onlyCatalogue as $col) echo "  - $col\n";

echo "\nColonnes COMMUNES:\n";
$common = array_intersect($articlesSchema, $catalogueSchema);
foreach($common as $col) echo "  - $col\n";

echo "\n=== USAGE ACTUEL ===\n";
$countArticles = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
$countCatalogue = $pdo->query("SELECT COUNT(*) FROM articles_catalogue")->fetchColumn();
echo "articles: $countArticles lignes\n";
echo "articles_catalogue: $countCatalogue lignes\n";

echo "\n=== RECOMMANDATION ===\n";
if($countCatalogue == 0 || $countCatalogue < 5) {
    echo "⚠️ 'articles_catalogue' est presque vide ($countCatalogue lignes)\n";
    echo "✅ RECOMMANDATION: Fusionner dans 'articles' et supprimer 'articles_catalogue'\n";
} else {
    echo "Les deux tables sont utilisées.\n";
    echo "Analyse nécessaire pour déterminer la stratégie de fusion.\n";
}

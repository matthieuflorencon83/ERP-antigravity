<?php
// tools/test_funnel_api.php
require_once __DIR__ . '/../db.php';

echo "<h3>Testing Funnel API Endpoints</h3>";

// Test 1: Fournisseurs
echo "<h4>1. Fournisseurs</h4>";
$stmt = $pdo->query("SELECT id, nom FROM fournisseurs");
$fournisseurs = $stmt->fetchAll();
echo "Count: " . count($fournisseurs) . "<br>";
foreach($fournisseurs as $f) echo "- {$f['nom']}<br>";

// Test 2: Familles
echo "<h4>2. Familles</h4>";
$stmt = $pdo->query("SELECT id, designation FROM familles_articles");
$familles = $stmt->fetchAll();
echo "Count: " . count($familles) . "<br>";
foreach($familles as $f) echo "- {$f['designation']}<br>";

// Test 3: Sous-Familles (for first family)
if(count($familles) > 0) {
    echo "<h4>3. Sous-Familles (for {$familles[0]['designation']})</h4>";
    $stmt = $pdo->prepare("SELECT id, designation FROM sous_familles_articles WHERE famille_id = ?");
    $stmt->execute([$familles[0]['id']]);
    $subs = $stmt->fetchAll();
    echo "Count: " . count($subs) . "<br>";
    foreach($subs as $s) echo "- {$s['designation']}<br>";
}

// Test 4: Articles
echo "<h4>4. Articles</h4>";
$stmt = $pdo->query("SELECT COUNT(*) FROM articles");
echo "Total articles: " . $stmt->fetchColumn() . "<br>";

// Test 5: Finitions
echo "<h4>5. Finitions</h4>";
$stmt = $pdo->query("SELECT code_ral, nom_couleur FROM finitions");
$fins = $stmt->fetchAll();
echo "Count: " . count($fins) . "<br>";
foreach($fins as $f) echo "- RAL {$f['code_ral']} - {$f['nom_couleur']}<br>";

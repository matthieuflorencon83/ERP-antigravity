<?php
// debug_metrage.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';
require_once 'functions.php';

echo "<h1>Debug Métrage</h1>";

// 1. Check DB Connection
echo "<h2>1. DB Connection</h2>";
if($pdo) echo "<div style='color:green'>OK</div>";
else echo "<div style='color:red'>FAIL</div>";

// 2. Check Metrage Types
echo "<h2>2. Metrage Types</h2>";
$types = $pdo->query("SELECT * FROM metrage_types")->fetchAll();
echo "Found " . count($types) . " types.<br>";
foreach($types as $t) {
    echo "- [ID: {$t['id']}] " . h($t['nom']) . "<br>";
}

// 3. Simulate get_form for first type
if(!empty($types)) {
    $first_id = $types[0]['id'];
    echo "<h2>3. Simulate Ajax (Type ID: $first_id)</h2>";
    
    $_GET['type_id'] = $first_id;
    // Include ajax file logic (simulated)
    
    $points = $pdo->prepare("SELECT * FROM metrage_points_controle WHERE metrage_type_id = ? ORDER BY ordre");
    $points->execute([$first_id]);
    $items = $points->fetchAll();
    
    echo "Found " . count($items) . " points.<br>";
    if(count($items) > 0) {
        echo "<div style='border:1px solid #ccc; padding:10px; background:#f9f9f9'>";
        foreach ($items as $p) {
            echo "Field: " . h($p['label']) . " (" . $p['type_saisie'] . ")<br>";
        }
        echo "</div>";
    } else {
         echo "<div style='color:red'>NO POINTS FOUND! SEEDING REQUIRED?</div>";
    }
} else {
    echo "<div style='color:red'>NO TYPES FOUND! SEEDING REQUIRED.</div>";
}
?>

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require 'db.php';

echo "<h2>Debug Affaires</h2>";

// 1. Count
try {
    $stmt = $pdo->query("SELECT count(*) FROM affaires");
    echo "Total rows in 'affaires': " . $stmt->fetchColumn() . "<br>";
} catch(Exception $e) {
    echo "Error counting: " . $e->getMessage() . "<br>";
}

// 2. Query used in Studio
echo "<h3>Studio Query Test</h3>";
$sql = "SELECT a.id, a.nom_affaire, c.nom as client_nom 
        FROM affaires a 
        LEFT JOIN clients c ON a.client_id = c.id 
        ORDER BY a.id DESC LIMIT 50";
echo "Probing SQL: $sql <br>";

try {
    $stmt = $pdo->query($sql);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Rows returned: " . count($res) . "<br>";
    if(count($res) === 0) {
        echo "<b>Result is empty!</b><br>";
        // Check raw table content
        echo "<h3>Raw Probing</h3>";
        $raw = $pdo->query("SELECT * FROM affaires LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($raw, true) . "</pre>";
    } else {
        echo "<pre>" . print_r($res, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<b>SQL Error:</b> " . $e->getMessage();
}
?>

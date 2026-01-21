<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$dsn = "mysql:host=localhost;dbname=antigravity;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, "root", "root"); // Correct config
    echo "Connected.<br>";
    $stmt = $pdo->query("SELECT id, nom_affaire, numero_prodevis FROM affaires LIMIT 5");
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Rows found: " . count($res) . "<br><pre>";
    print_r($res);
    echo "</pre>";
} catch(Exception $e) { 
    echo "Error: " . $e->getMessage(); 
}
?>

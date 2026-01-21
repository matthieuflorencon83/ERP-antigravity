<?php
require_once 'db.php';
$stmt = $pdo->query("SELECT id, designation_commerciale FROM articles_catalogue LIMIT 10");
$arts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Articles Found: " . count($arts) . "\n";
print_r($arts);

$stmt2 = $pdo->query("SELECT * FROM stocks LIMIT 5");
echo "\nStocks Found:\n";
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

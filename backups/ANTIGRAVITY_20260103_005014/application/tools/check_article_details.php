<?php
// tools/check_article_details.php
require_once __DIR__ . '/../db.php';

echo "=== ARTICLES TABLE ===\n";
$stmt = $pdo->query("SELECT * FROM articles LIMIT 1");
$sample = $stmt->fetch(PDO::FETCH_ASSOC);
if($sample) {
    foreach($sample as $k => $v) echo "$k: $v\n";
}

echo "\n=== ARTICLES_CATALOGUE TABLE ===\n";
$stmt = $pdo->query("SELECT * FROM articles_catalogue LIMIT 1");
$sample = $stmt->fetch(PDO::FETCH_ASSOC);
if($sample) {
    foreach($sample as $k => $v) echo "$k: $v\n";
}

echo "\n=== FINITIONS TABLE ===\n";
$stmt = $pdo->query("SELECT * FROM finitions LIMIT 1");
$sample = $stmt->fetch(PDO::FETCH_ASSOC);
if($sample) {
    foreach($sample as $k => $v) echo "$k: $v\n";
}

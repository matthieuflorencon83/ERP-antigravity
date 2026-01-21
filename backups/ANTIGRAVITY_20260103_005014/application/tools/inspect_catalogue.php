<?php
require_once __DIR__ . '/../db.php';
$stmt = $pdo->query("DESCRIBE articles_catalogue");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
foreach($cols as $c) echo $c['Field'] . "\n";
echo "</pre>";

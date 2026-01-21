<?php
require_once __DIR__ . '/../db.php';
$stmt = $pdo->query("DESCRIBE besoins_chantier");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($cols);
echo "</pre>";

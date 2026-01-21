<?php
require_once __DIR__ . '/../db.php';
$stmt = $pdo->query("SELECT COUNT(*) FROM metrage_types");
echo "METRAGE TYPES COUNT: " . $stmt->fetchColumn() . "\n";

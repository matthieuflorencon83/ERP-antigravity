<?php
require_once __DIR__ . '/../db.php';
$stmt = $pdo->query("DESCRIBE modeles_profils");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

<?php
require 'db.php';
$stmt = $pdo->query("SHOW CREATE TABLE tasks");
$res = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($res);
echo "</pre>";
?>

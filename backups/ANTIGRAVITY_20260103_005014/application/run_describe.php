<?php
require_once 'db.php';
$stmt = $pdo->query('DESCRIBE metrage_etapes');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

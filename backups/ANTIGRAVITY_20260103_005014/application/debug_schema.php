<?php
require 'db.php';
try {
    $stmt = $pdo->query("SHOW CREATE TABLE metrage_lignes");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    // Determine key name usually 'Create Table'
    $key = array_keys($row)[1];
    echo "<pre>" . $row[$key] . "</pre>";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>

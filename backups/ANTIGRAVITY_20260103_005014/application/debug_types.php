<?php
require 'db.php';
try {
    $stmt = $pdo->query("SELECT * FROM metrage_types ORDER BY nom ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>

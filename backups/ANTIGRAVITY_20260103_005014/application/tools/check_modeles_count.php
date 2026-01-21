<?php
require_once __DIR__ . '/../db.php';
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM modeles_profils");
    echo "COUNT: " . $stmt->fetchColumn();
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
?>

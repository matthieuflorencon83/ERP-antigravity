<?php
require_once 'db.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM metrage_etapes ORDER BY categorie, ordre");
    $etapes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($etapes, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

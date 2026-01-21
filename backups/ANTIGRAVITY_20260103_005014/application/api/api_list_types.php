<?php
// api/api_list_types.php - List all metrage types
require_once '../db.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, nom, categorie FROM metrage_types ORDER BY categorie, nom");
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Also get etapes count per category
    $stmt2 = $pdo->query("SELECT categorie, COUNT(*) as cnt FROM metrage_etapes GROUP BY categorie");
    $etapesCounts = $stmt2->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo json_encode([
        'types' => $types,
        'etapes_per_category' => $etapesCounts
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

<?php
// api/api_test_metrage.php - Test endpoint to debug save issues
require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

try {
    // 1. Check metrage_types table
    $types = $pdo->query("DESCRIBE metrage_types")->fetchAll(PDO::FETCH_COLUMN);
    
    // 2. Check metrage_lignes table
    $lignes = $pdo->query("DESCRIBE metrage_lignes")->fetchAll(PDO::FETCH_COLUMN);
    
    // 3. Check if type_id=1 exists
    $stmt = $pdo->query("SELECT id, nom FROM metrage_types LIMIT 3");
    $sample_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Test simple insert (if POST)
    $insert_test = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['test_insert'])) {
        $sql = "INSERT INTO metrage_lignes (intervention_id, metrage_type_id, description, statut, donnees_json, created_at) 
                VALUES (1, 1, 'Test V4', 'VALIDÉ', '{}', NOW())";
        $pdo->exec($sql);
        $insert_test = ['success' => true, 'id' => $pdo->lastInsertId()];
    }

    echo json_encode([
        'success' => true,
        'metrage_types_columns' => $types,
        'metrage_lignes_columns' => $lignes,
        'sample_types' => $sample_types,
        'insert_test' => $insert_test
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

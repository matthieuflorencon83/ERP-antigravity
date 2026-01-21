<?php
// install/run_metrage_etapes.php
// Execute metrage_etapes.sql script
require_once '../db.php';

header('Content-Type: application/json');

try {
    // Read the SQL file
    $sqlFile = __DIR__ . '/metrage_etapes.sql';
    $sql = file_get_contents($sqlFile);
    
    if (!$sql) {
        throw new Exception("Cannot read SQL file");
    }
    
    // Execute multi-statement SQL
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $pdo->exec($sql);
    
    // Verify
    $count = $pdo->query("SELECT COUNT(*) FROM metrage_etapes")->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'message' => "Table metrage_etapes créée avec succès",
        'etapes_count' => $count
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}

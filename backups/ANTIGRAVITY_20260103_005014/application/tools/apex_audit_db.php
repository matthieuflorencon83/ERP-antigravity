<?php
// APEX AUDIT DB - "The Truth"
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

$response = [
    'metadata' => [
        'timestamp' => date('Y-m-d H:i:s'),
        'host' => DB_HOST,
        'db' => DB_NAME
    ],
    'tables' => []
];

try {
    // 1. Get Tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // 2. Get Row Count
        $countStmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        $rowCount = $countStmt->fetchColumn();

        // 3. Get Structure
        $descStmt = $pdo->query("DESCRIBE `$table`");
        $columns = $descStmt->fetchAll(PDO::FETCH_ASSOC);

        $colsSimple = [];
        foreach ($columns as $c) {
            $colsSimple[$c['Field']] = $c['Type'] . ($c['Key'] ? " [{$c['Key']}]" : "");
        }

        $response['tables'][$table] = [
            'rows' => (int)$rowCount,
            'columns' => $colsSimple
        ];
    }

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

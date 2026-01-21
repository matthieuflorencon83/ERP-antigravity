<?php
require_once __DIR__ . '/../db.php';
try {
    echo "--- AFFAIRES TABLE SCHEMA ---\n";
    $stmt = $pdo->query("DESCRIBE affaires");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}

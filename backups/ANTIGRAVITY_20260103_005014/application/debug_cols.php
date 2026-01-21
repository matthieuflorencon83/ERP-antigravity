<?php
// require_once 'auth.php';
require_once 'db.php';

function getCols($table) {
    global $pdo;
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch(Exception $e) { return [$e->getMessage()]; }
}

echo "COMMANDES_ACHATS: " . implode(", ", getCols('commandes_achats')) . "\n";
echo "COMMANDES_EXPRESS: " . implode(", ", getCols('commandes_express')) . "\n";

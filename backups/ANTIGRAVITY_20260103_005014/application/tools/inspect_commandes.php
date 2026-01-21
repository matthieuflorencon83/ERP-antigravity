<?php
require_once __DIR__ . '/../db.php';
try {
    echo "--- COMMANDES_ACHATS SCHEMA ---\n";
    $stmt = $pdo->query("DESCRIBE commandes_achats");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        if ($col['Field'] === 'lieu_livraison') {
            echo ">> " . $col['Field'] . " (" . $col['Type'] . ")\n";
        } else {
            echo $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    }
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}

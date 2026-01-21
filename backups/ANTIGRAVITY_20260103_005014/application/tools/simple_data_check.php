<?php
require_once __DIR__ . '/../db.php';

echo "<h2>Données Test</h2>";

try {
    // Affaires
    $stmt = $pdo->query("SELECT COUNT(*) FROM affaires");
    $count = $stmt->fetchColumn();
    echo "<p>Affaires: $count</p>";
    
    if($count > 0) {
        $stmt = $pdo->query("SELECT id, reference FROM affaires LIMIT 10");
        while($row = $stmt->fetch()) {
            echo "- ID {$row['id']}: {$row['reference']}<br>";
        }
    }
    
    // Commandes
    $stmt = $pdo->query("SELECT COUNT(*) FROM commandes_achats");
    $count = $stmt->fetchColumn();
    echo "<p>Commandes: $count</p>";
    
} catch(Exception $e) {
    echo "Erreur: " . $e->getMessage();
}

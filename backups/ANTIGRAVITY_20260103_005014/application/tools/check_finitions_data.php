<?php
// tools/check_finitions_data.php
require_once __DIR__ . '/../db.php';

try {
    $stmt = $pdo->query("SELECT count(*) FROM finitions");
    echo "Finitions Count: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT * FROM finitions LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        foreach($rows as $r) echo "{$r['id']}: {$r['code_ral']} - {$r['nom_couleur']} ({$r['aspect']})\n";
    } else {
        echo "Table is empty.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

<?php
// tools/check_devis_tables.php
require_once __DIR__ . '/../db.php';

echo "=== TABLES DEVIS ===\n\n";

// Check devis table
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM devis");
    $count = $stmt->fetchColumn();
    echo "Table 'devis': $count lignes\n";
    
    if($count > 0) {
        $stmt = $pdo->query("SELECT id, created_at FROM devis LIMIT 3");
        foreach($stmt->fetchAll() as $d) {
            echo "  - Devis ID {$d['id']} (créé le {$d['created_at']})\n";
        }
    }
} catch(PDOException $e) {
    echo "Table 'devis': N'EXISTE PAS\n";
}

echo "\n";

// Check devis_details table
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM devis_details");
    $count = $stmt->fetchColumn();
    echo "Table 'devis_details': $count lignes\n";
    
    if($count > 0) {
        $stmt = $pdo->query("SELECT id, devis_id FROM devis_details LIMIT 3");
        foreach($stmt->fetchAll() as $d) {
            echo "  - Détail ID {$d['id']} (devis {$d['devis_id']})\n";
        }
    }
} catch(PDOException $e) {
    echo "Table 'devis_details': N'EXISTE PAS\n";
}

echo "\n=== RECHERCHE DANS LE CODE ===\n";

// Search for devis usage in PHP files
$files = glob(__DIR__ . '/../*.php');
$found = false;

foreach($files as $file) {
    $content = file_get_contents($file);
    if(stripos($content, 'devis') !== false && stripos($content, 'FROM devis') !== false) {
        echo "✗ Utilisé dans: " . basename($file) . "\n";
        $found = true;
    }
}

if(!$found) {
    echo "✓ Aucune utilisation trouvée dans les fichiers PHP racine\n";
}

echo "\n=== RECOMMANDATION ===\n";
echo "Si les tables sont vides et non utilisées, vous pouvez les supprimer.\n";

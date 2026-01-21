<?php
// tools/drop_devis_tables.php
require_once __DIR__ . '/../db.php';

echo "<h3>Suppression des tables DEVIS</h3>";

try {
    // Drop devis_details first (foreign key)
    $pdo->exec("DROP TABLE IF EXISTS devis_details");
    echo "✓ Table 'devis_details' supprimée<br>";
    
    // Drop devis
    $pdo->exec("DROP TABLE IF EXISTS devis");
    echo "✓ Table 'devis' supprimée<br>";
    
    echo "<hr><div class='alert alert-success'><strong>✅ Tables DEVIS supprimées avec succès</strong></div>";
    
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur: " . $e->getMessage() . "</div>";
}

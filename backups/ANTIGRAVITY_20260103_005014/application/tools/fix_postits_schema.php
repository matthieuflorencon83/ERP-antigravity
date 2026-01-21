<?php
// tools/fix_postits_schema.php
require_once __DIR__ . '/../db.php';

echo "<h2>🔧 Correction Schéma Dashboard Memo</h2>";

try {
    // Check current schema
    $stmt = $pdo->query("DESCRIBE dashboard_postits");
    $currentCols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h4>Colonnes actuelles:</h4>";
    echo "<ul>";
    foreach($currentCols as $col) {
        echo "<li>$col</li>";
    }
    echo "</ul>";
    
    // Required columns for position and size
    $requiredCols = [
        'x_pos' => 'INT DEFAULT 20',
        'y_pos' => 'INT DEFAULT 20',
        'width' => 'INT DEFAULT 220',
        'height' => 'INT DEFAULT 220',
        'z_index' => 'INT DEFAULT 1'
    ];
    
    echo "<h4>Ajout colonnes manquantes:</h4>";
    $added = 0;
    
    foreach($requiredCols as $col => $def) {
        if(!in_array($col, $currentCols)) {
            $pdo->exec("ALTER TABLE dashboard_postits ADD COLUMN $col $def");
            echo "<p class='text-success'>✓ Colonne '$col' ajoutée</p>";
            $added++;
        } else {
            echo "<p class='text-info'>○ Colonne '$col' existe déjà</p>";
        }
    }
    
    echo "<hr><div class='alert alert-success'>";
    echo "<h4>✅ Schéma Corrigé</h4>";
    echo "<p>$added colonnes ajoutées. Les mémos garderont maintenant leur position et taille.</p>";
    echo "</div>";
    
    // Show final schema
    echo "<h4>Schéma Final:</h4>";
    $stmt = $pdo->query("DESCRIBE dashboard_postits");
    $finalCols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table class='table table-sm'>";
    echo "<tr><th>Colonne</th><th>Type</th><th>Default</th></tr>";
    foreach($finalCols as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur: " . $e->getMessage() . "</div>";
}

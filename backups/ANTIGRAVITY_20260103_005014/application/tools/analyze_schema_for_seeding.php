<?php
// tools/analyze_schema_for_seeding.php
require_once __DIR__ . '/../db.php';

echo "<h1>📡 PHASE 1: ANALYSE SCHÉMA DATABASE</h1>";

$tables = ['clients', 'affaires', 'metrages', 'metrage_lignes', 'metrage_types', 'finitions', 'fournisseurs'];

foreach($tables as $table) {
    echo "<h3>Table: $table</h3>";
    
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll();
        
        echo "<table class='table table-sm table-bordered'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach($columns as $col) {
            echo "<tr>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch(PDOException $e) {
        echo "<p class='text-danger'>Erreur: {$e->getMessage()}</p>";
    }
}

// Check metrage_types
echo "<h3>Metrage Types Disponibles</h3>";
$stmt = $pdo->query("SELECT id, nom, code FROM metrage_types");
$types = $stmt->fetchAll();
echo "<ul>";
foreach($types as $t) {
    echo "<li>ID {$t['id']}: {$t['nom']} (Code: {$t['code']})</li>";
}
echo "</ul>";

// Check finitions
echo "<h3>Finitions Disponibles</h3>";
$stmt = $pdo->query("SELECT id, nom_couleur, code_ral FROM finitions LIMIT 10");
$finitions = $stmt->fetchAll();
echo "<ul>";
foreach($finitions as $f) {
    echo "<li>ID {$f['id']}: {$f['nom_couleur']} (RAL: {$f['code_ral']})</li>";
}
echo "</ul>";

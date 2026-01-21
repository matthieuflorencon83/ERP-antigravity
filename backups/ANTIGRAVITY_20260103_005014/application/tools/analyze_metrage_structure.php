<?php
require_once __DIR__ . '/../db.php';

echo "<h2>📋 Structure Tables Métrage</h2>";

// metrage_interventions
echo "<h3>metrage_interventions</h3>";
$stmt = $pdo->query("DESCRIBE metrage_interventions");
$cols = $stmt->fetchAll();
echo "<table class='table table-sm'><tr><th>Field</th><th>Type</th><th>Null</th></tr>";
foreach($cols as $c) echo "<tr><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Null']}</td></tr>";
echo "</table>";

// metrage_lignes
echo "<h3>metrage_lignes</h3>";
$stmt = $pdo->query("DESCRIBE metrage_lignes");
$cols = $stmt->fetchAll();
echo "<table class='table table-sm'><tr><th>Field</th><th>Type</th><th>Null</th></tr>";
foreach($cols as $c) echo "<tr><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Null']}</td></tr>";
echo "</table>";

// Sample existing data
echo "<h3>Exemple données existantes</h3>";
$stmt = $pdo->query("SELECT * FROM metrage_lignes LIMIT 1");
$sample = $stmt->fetch();
if($sample) {
    echo "<pre>" . print_r($sample, true) . "</pre>";
} else {
    echo "<p>Aucune donnée existante</p>";
}

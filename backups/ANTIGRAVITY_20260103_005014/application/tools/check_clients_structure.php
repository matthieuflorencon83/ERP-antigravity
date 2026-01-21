<?php
require_once __DIR__ . '/../db.php';

echo "<h2>Structure table clients</h2>";
$stmt = $pdo->query("DESCRIBE clients");
$cols = $stmt->fetchAll();
echo "<table class='table table-sm'>";
echo "<tr><th>Field</th><th>Type</th></tr>";
foreach($cols as $c) {
    echo "<tr><td><strong>{$c['Field']}</strong></td><td>{$c['Type']}</td></tr>";
}
echo "</table>";

echo "<h2>Structure table affaires</h2>";
$stmt = $pdo->query("DESCRIBE affaires");
$cols = $stmt->fetchAll();
echo "<table class='table table-sm'>";
echo "<tr><th>Field</th><th>Type</th></tr>";
foreach($cols as $c) {
    echo "<tr><td><strong>{$c['Field']}</strong></td><td>{$c['Type']}</td></tr>";
}
echo "</table>";

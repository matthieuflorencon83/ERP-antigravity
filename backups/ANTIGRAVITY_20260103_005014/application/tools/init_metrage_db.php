<?php
require_once __DIR__ . '/../db.php';

$schemaFile = __DIR__ . '/../install/metrage_schema.sql';
if (!file_exists($schemaFile)) {
    die("❌ Schema file not found: $schemaFile\n");
}

$sql = file_get_contents($schemaFile);

echo "INITIALIZING METRAGE SCHEMA...\n";

try {
    $pdo->exec($sql);
    echo "✅ Schema executed successfully.\n";
} catch (PDOException $e) {
    echo "❌ Error executing schema: " . $e->getMessage() . "\n";
}

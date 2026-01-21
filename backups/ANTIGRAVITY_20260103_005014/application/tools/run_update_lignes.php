<?php
require_once __DIR__ . '/../db.php';
$sql = file_get_contents(__DIR__ . '/update_schema_lignes_designation.sql');
try {
    $pdo->exec($sql);
    echo "✅ Colonne 'designation' ajoutée à lignes_achat.\n";
} catch (PDOException $e) {
    echo "ℹ️ Note SQL : " . $e->getMessage() . "\n";
}
?>

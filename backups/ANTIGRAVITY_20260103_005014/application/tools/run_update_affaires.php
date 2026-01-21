<?php
require_once __DIR__ . '/../db.php';
$sql = file_get_contents(__DIR__ . '/update_schema_affaires_sales.sql');
try {
    $pdo->exec($sql);
    echo "✅ Champs 'montant_ht' et 'date_signature' ajoutés à la table 'affaires'.\n";
} catch (PDOException $e) {
    echo "ℹ️ Note SQL : " . $e->getMessage() . "\n";
}
?>

<?php
require_once __DIR__ . '/../db.php';
$sql = file_get_contents(__DIR__ . '/update_schema_devis.sql');
try {
    $pdo->exec($sql);
    echo "✅ Tables 'devis' et 'devis_details' créées avec succès.\n";
} catch (PDOException $e) {
    echo "❌ Erreur SQL : " . $e->getMessage() . "\n";
}
?>

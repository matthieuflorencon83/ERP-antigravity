<?php
require_once __DIR__ . '/../db.php';
$sql = file_get_contents(__DIR__ . '/seed_modeles.sql');
try {
    $pdo->exec($sql);
    echo "✅ Modèles injectés dans modeles_profils.\n";
} catch (PDOException $e) {
    echo "ℹ️ Note SQL : " . $e->getMessage() . "\n";
}
?>

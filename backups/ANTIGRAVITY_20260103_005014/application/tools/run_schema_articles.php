<?php
require_once __DIR__ . '/../db.php';

$sql_file = __DIR__ . '/update_schema_articles.sql';
if (!file_exists($sql_file)) die("Fichier SQL introuvable.");

$sql = file_get_contents($sql_file);

try {
    $pdo->exec($sql);
    echo "✅ Table 'articles' créée/vérifiée avec succès.\n";
} catch (PDOException $e) {
    echo "❌ Erreur SQL : " . $e->getMessage() . "\n";
}
?>

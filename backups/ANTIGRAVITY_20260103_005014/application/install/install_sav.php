<?php
// Script d'installation du schéma SAV
require_once __DIR__ . '/../db.php';

$sqlFile = __DIR__ . '/sav_schema.sql';
if (!file_exists($sqlFile)) {
    die("❌ Fichier SQL introuvable : $sqlFile");
}

$sql = file_get_contents($sqlFile);

try {
    $pdo->exec($sql);
    echo "✅ Tables SAV installées avec succès.";
} catch (PDOException $e) {
    die("❌ Erreur SQL : " . $e->getMessage());
}

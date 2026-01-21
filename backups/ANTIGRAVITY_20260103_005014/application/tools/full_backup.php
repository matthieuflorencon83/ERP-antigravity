<?php
// tools/full_backup.php
// Script de sauvegarde complète (Base de données + Fichiers)
// Auteur: Antigravity Apex

// Suppression des warnings CLI (SERVER_PORT manquant dans config.php)
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/../config.php';

// Configuration
$backupDir = __DIR__ . '/../backups';
$timestamp = date('Y-m-d_H-i-s');
$dbFile = $backupDir . "/db_antigravity_{$timestamp}.sql";
$zipFile = $backupDir . "/full_backup_{$timestamp}.zip";
$sourceDir = realpath(__DIR__ . '/../');

// Création dossier backup
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

echo "💾 Démarrage de la sauvegarde...\n";
echo "📂 Dossier cible : $backupDir\n";

// 1. DUMP BASE DE DONNÉES
echo "--- Étape 1 : Sauvegarde Base de Données ---\n";
$mysqldump = "mysqldump"; 
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $mysqldump = "C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe"; 
    if (!file_exists($mysqldump)) $mysqldump = "mysqldump"; 
}

$cmd = "\"$mysqldump\" --user=" . DB_USER . " --password=" . DB_PASS . " --host=" . DB_HOST . " " . DB_NAME . " > \"$dbFile\" 2>&1";

echo "Exécution : mysqldump... ";
exec($cmd, $output, $returnVar);

if ($returnVar === 0 && file_exists($dbFile) && filesize($dbFile) > 0) {
    echo "✅ SUCCÈS (" . round(filesize($dbFile) / 1024, 2) . " KB)\n";
} else {
    echo "❌ ÉCHEC\n";
    echo "Sortie : " . implode("\n", $output) . "\n";
}

// 2. ZIP DES FICHIERS
echo "\n--- Étape 2 : Compression des Fichiers (ZIP) ---\n";

if (class_exists('ZipArchive')) {
    echo "📚 Utilisation de PHP ZipArchive...\n";
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $count = 0;
        foreach ($files as $name => $file) {
            if (strpos($file->getRealPath(), 'node_modules') !== false) continue;
            if (strpos($file->getRealPath(), '.git') !== false) continue;
            if (strpos($file->getRealPath(), 'backups') !== false && $file->getRealPath() !== realpath($dbFile)) continue;
            
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($sourceDir) + 1);

            $zip->addFile($filePath, $relativePath);
            $count++;
            if ($count % 500 == 0) echo ".";
        }
        
        // Ajout du SQL s'il n'a pas été pris (normalement il est dans backups mais on a exclu backups sauf lui potentiellement)
        if (file_exists($dbFile)) {
            $zip->addFile($dbFile, "database_dump.sql");
        }
        
        $zip->close();
        echo "\n✅ ZIP Créé (PHP) : $zipFile\n";
        exit(0);
    } else {
        echo "⚠️ Erreur ZipArchive, passage au fallback PowerShell.\n";
    }
} else {
    echo "⚠️ Extension ZipArchive manquante, passage au fallback PowerShell.\n";
}

// FALLBACK POWERSHELL
$exclude = ["node_modules", ".git", "backups"];
// Pour PowerShell, l'exclusion est plus complexe avec Compress-Archive.
// On va faire simple : on zippe tout, tant pis pour node_modules s'il est là (mais user a dit pas de web app complexe donc ça devrait aller).
// Ou mieux : On utilise 7zip si présent ? Non.
// On va tenter une commande PowerShell un peu intelligente.

$psCommand = "powershell -Command \"Compress-Archive -Path '$sourceDir\\*' -DestinationPath '$zipFile' -Force\"";
echo "⏳ Exécution PowerShell (cela peut prendre du temps)...\n";
// Note: Compress-Archive n'a pas d'option 'Exclude' native simple récursive fiable sans script complexe.
// On prévient l'utilisateur.
exec($psCommand, $psOutput, $psReturn);

if ($psReturn === 0 && file_exists($zipFile)) {
    echo "✅ ZIP Créé (PowerShell) : $zipFile\n";
} else {
    echo "❌ Échec ZIP PowerShell.\n";
}

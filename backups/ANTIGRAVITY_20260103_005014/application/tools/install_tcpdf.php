<?php
/**
 * install_tcpdf.php
 * Script d'installation manuelle de TCPDF (Version PowerShell).
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$version = "6.7.5";
$url = "https://github.com/tecnickcom/TCPDF/archive/refs/tags/{$version}.zip";
$baseDir = dirname(__DIR__);
$libDir = $baseDir . '/lib';
$zipFile = $libDir . '/tcpdf.zip';
$finalDir = $libDir . '/tcpdf';

echo "=== INFOS ===\n";
echo "Base Dir: $baseDir\n";

// 0. Création du dossier lib
if (!is_dir($libDir)) {
    echo "0. Création du dossier 'lib'...\n";
    if (!mkdir($libDir, 0777, true)) {
        die("ERREUR CRITIQUE : Impossible de créer le dossier $libDir\n");
    }
}

// 1. Téléchargement
if (!file_exists($zipFile) || filesize($zipFile) < 1000) {
    echo "1. Téléchargement de TCPDF...\n";
    $context = stream_context_create(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
    $data = file_get_contents($url, false, $context);
    
    if (!$data) {
        // Fallback Curl
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
    }
    
    if (!$data) die("ERREUR : Echec du téléchargement.\n");
    file_put_contents($zipFile, $data);
} else {
    echo "1. Fichier ZIP déjà présent.\n";
}

// 2. Extraction (Méthode Hybride)
echo "2. Extraction de l'archive...\n";

$extractedFolder = $libDir . "/TCPDF-" . $version;
$success = false;

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        $zip->extractTo($libDir);
        $zip->close();
        $success = true;
        echo "   > Via ZipArchive (PHP).\n";
    }
} 

if (!$success) {
    echo "   > Tentative via PowerShell (Expand-Archive)...\n";
    // Important: Chemins absolus windows
    $winZip = str_replace('/', '\\', $zipFile);
    $winDest = str_replace('/', '\\', $libDir);
    
    $cmd = "powershell -command \"Expand-Archive -Path '$winZip' -DestinationPath '$winDest' -Force\"";
    exec($cmd, $output, $returnVar);
    
    if ($returnVar === 0) {
        $success = true;
        echo "   > Extraction PowerShell réussie.\n";
    } else {
        echo "   > Erreur PowerShell : " . implode("\n", $output) . "\n";
    }
}

if (!$success) die("ERREUR : Impossible d'extraire le ZIP.\n");

// 3. Installation Finale
echo "3. Finalisation...\n";

// Ménage ancien dossier
if (file_exists($finalDir)) {
    // On essaie de renommer l'ancien pour ne pas bloquer
    @rename($finalDir, $finalDir . '_old_' . time());
}

if (is_dir($extractedFolder)) {
    if (rename($extractedFolder, $finalDir)) {
        echo "SUCCES : TCPDF installé dans $finalDir\n";
        @unlink($zipFile);
    } else {
        echo "ERREUR : Impossible de renommer le dossier extrait.\n";
    }
} else {
    echo "ERREUR : Le dossier extrait '$extractedFolder' est introuvable.\n";
}
?>

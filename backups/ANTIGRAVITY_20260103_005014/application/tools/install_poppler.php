<?php
/**
 * install_poppler.php (Fallback Robust)
 */

$url = "https://github.com/oschwartz10612/poppler-windows/releases/download/v24.02.0-0/Release-24.02.0-0.zip";
$binDir = __DIR__ . '/../bin'; // tools/../bin = bin
$zipFile = $binDir . '/poppler.zip';
$extractDir = $binDir . '/poppler_temp';
$finalExe = $binDir . '/pdftotext.exe';

echo "DIR: $binDir\n";

if (!is_dir($binDir)) {
    if (!mkdir($binDir, 0777, true)) {
        die("ECHEC CREATION DOSSIER BIN\n");
    }
}

// 1. Download
echo "Downloading...\n";
$data = file_get_contents($url, false, stream_context_create([
    "ssl" => ["verify_peer"=>false, "verify_peer_name"=>false]
]));

if (!$data) die("Download Failed\n");

file_put_contents($zipFile, $data);
echo "Download OK (" . strlen($data) . " bytes)\n";

// 2. Unzip via PowerShell
echo "Unzipping...\n";
$zipPath = realpath($zipFile);
$destPath = realpath($binDir) . DIRECTORY_SEPARATOR . 'poppler_temp';

$cmd = "powershell -command \"Expand-Archive -Path '$zipPath' -DestinationPath '$destPath' -Force\"";
shell_exec($cmd);

// 3. Search
echo "Searching pdftotext.exe...\n";
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($destPath));
foreach ($iterator as $file) {
    if ($file->getFilename() === 'pdftotext.exe') {
        rename($file->getPathname(), $finalExe);
        echo "MOVED TO $finalExe\n";
        break;
    }
}

echo "DONE.\n";
?>

<?php
// tools/action2_archive_zombie_files.php

echo "<h2>🧟 ACTION 2: ARCHIVAGE FICHIERS ZOMBIES</h2>";

$zombieDir = __DIR__ . '/../backups/zombie_files_' . date('Y-m-d');
if(!is_dir($zombieDir)) {
    mkdir($zombieDir, 0755, true);
    echo "<p>✓ Dossier créé: <code>$zombieDir</code></p>";
}

$zombiePatterns = ['test_', '_backup', '_old', '_temp'];
$phpFiles = glob(__DIR__ . '/../*.php');

$archived = [];
$errors = [];

echo "<table class='table table-sm'>";
echo "<tr><th>Fichier</th><th>Taille</th><th>Action</th><th>Résultat</th></tr>";

foreach($phpFiles as $file) {
    $basename = basename($file);
    $isZombie = false;
    
    foreach($zombiePatterns as $pattern) {
        if(stripos($basename, $pattern) !== false) {
            $isZombie = true;
            break;
        }
    }
    
    if($isZombie) {
        $size = filesize($file);
        $sizeKB = number_format($size / 1024, 2);
        
        try {
            $destination = $zombieDir . '/' . $basename;
            
            // Copy then delete (safer than move)
            if(copy($file, $destination)) {
                unlink($file);
                $archived[] = $basename;
                echo "<tr class='table-success'>";
                echo "<td>$basename</td>";
                echo "<td>{$sizeKB} KB</td>";
                echo "<td>✓ ARCHIVÉ</td>";
                echo "<td><span class='text-success'>Déplacé vers backups/</span></td>";
                echo "</tr>";
            } else {
                throw new Exception("Échec copie");
            }
            
        } catch(Exception $e) {
            $errors[] = $basename;
            echo "<tr class='table-danger'>";
            echo "<td>$basename</td>";
            echo "<td>{$sizeKB} KB</td>";
            echo "<td>❌ ERREUR</td>";
            echo "<td><span class='text-danger'>{$e->getMessage()}</span></td>";
            echo "</tr>";
        }
    }
}

echo "</table>";

echo "<div class='alert alert-success'>";
echo "<h4>✅ ACTION 2 TERMINÉE</h4>";
echo "<p><strong>Archivés:</strong> " . count($archived) . " fichiers</p>";
echo "<p><strong>Erreurs:</strong> " . count($errors) . "</p>";
echo "<p><strong>Emplacement:</strong> <code>$zombieDir</code></p>";
echo "</div>";

if(count($archived) > 0) {
    echo "<div class='alert alert-info'>";
    echo "<h5>📦 Fichiers Archivés</h5>";
    echo "<ul>";
    foreach($archived as $f) {
        echo "<li>$f</li>";
    }
    echo "</ul>";
    echo "<p><em>Ces fichiers peuvent être restaurés depuis le dossier backups/ si nécessaire.</em></p>";
    echo "</div>";
}

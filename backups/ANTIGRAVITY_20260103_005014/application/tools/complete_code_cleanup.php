<?php
// tools/complete_code_cleanup.php
require_once __DIR__ . '/../db.php';

echo "<h1>🔧 NETTOYAGE COMPLET DU CODE</h1>";

// Mappings de remplacement
$replacements = [
    'articles_catalogue' => 'articles',
    'designation_commerciale' => 'designation',
    'nom_principal' => 'nom_principal', // Keep (correct)
    'familles' => 'familles_articles',
];

// Scan tous les fichiers PHP (sauf tools/ et backups/)
$phpFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/../', RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        // Exclure tools/, backups/, vendor/
        if (stripos($path, 'tools') === false && 
            stripos($path, 'backup') === false && 
            stripos($path, 'vendor') === false &&
            stripos($path, '_private') === false) {
            $phpFiles[] = $path;
        }
    }
}

echo "<p>Fichiers à scanner: <strong>" . count($phpFiles) . "</strong></p>";

$filesModified = [];
$totalReplacements = 0;

foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    $fileReplacements = 0;
    
    // Apply all replacements
    foreach ($replacements as $old => $new) {
        if ($old === $new) continue; // Skip identity
        
        $count = 0;
        $content = str_replace($old, $new, $content, $count);
        $fileReplacements += $count;
    }
    
    // Save if modified
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $filesModified[] = [
            'file' => str_replace(__DIR__ . '/../', '', $file),
            'replacements' => $fileReplacements
        ];
        $totalReplacements += $fileReplacements;
    }
}

echo "<h3>✅ Résultats</h3>";

if (count($filesModified) > 0) {
    echo "<table class='table table-sm'>";
    echo "<tr><th>Fichier</th><th>Remplacements</th></tr>";
    foreach ($filesModified as $fm) {
        echo "<tr class='table-success'>";
        echo "<td>{$fm['file']}</td>";
        echo "<td>{$fm['replacements']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='alert alert-success'>";
    echo "<h4>✅ NETTOYAGE TERMINÉ</h4>";
    echo "<p><strong>$totalReplacements</strong> remplacements dans <strong>" . count($filesModified) . "</strong> fichiers</p>";
    echo "</div>";
} else {
    echo "<div class='alert alert-info'>";
    echo "<h4>✓ Code déjà propre</h4>";
    echo "<p>Aucune référence obsolète trouvée.</p>";
    echo "</div>";
}

// Liste des fichiers modifiés
echo "<h4>Fichiers modifiés:</h4>";
echo "<ul>";
foreach ($filesModified as $fm) {
    echo "<li>{$fm['file']}</li>";
}
echo "</ul>";

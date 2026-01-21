<?php
// tools/auto_fix_breaking_changes.php
require_once __DIR__ . '/../db.php';

echo "<h1>🔧 CORRECTION AUTOMATIQUE - BREAKING CHANGES</h1>";

$fixes = [
    'articles_catalogue' => 'articles',
    'familles' => 'familles_articles',
];

$phpFiles = array_merge(
    glob(__DIR__ . '/../*.php'),
    glob(__DIR__ . '/../ajax/*.php'),
    glob(__DIR__ . '/../controllers/*.php')
);

// Exclude tools and backups
$phpFiles = array_filter($phpFiles, function($f) {
    return stripos($f, 'tools') === false && 
           stripos($f, 'backup') === false &&
           stripos($f, 'test_') === false;
});

echo "<h3>Fichiers à corriger: " . count($phpFiles) . "</h3>";

$totalReplacements = 0;
$filesModified = [];

foreach($phpFiles as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    $fileReplacements = 0;
    
    // Apply replacements
    foreach($fixes as $old => $new) {
        $count = 0;
        $content = str_replace($old, $new, $content, $count);
        $fileReplacements += $count;
    }
    
    // Save if modified
    if($content !== $originalContent) {
        file_put_contents($file, $content);
        $filesModified[] = [
            'file' => basename($file),
            'replacements' => $fileReplacements
        ];
        $totalReplacements += $fileReplacements;
    }
}

echo "<h3>Résultats</h3>";
echo "<table class='table table-sm'>";
echo "<tr><th>Fichier</th><th>Remplacements</th></tr>";

foreach($filesModified as $fm) {
    echo "<tr class='table-success'>";
    echo "<td>{$fm['file']}</td>";
    echo "<td>{$fm['replacements']}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<div class='alert alert-success'>";
echo "<h4>✅ CORRECTIONS APPLIQUÉES</h4>";
echo "<p><strong>$totalReplacements</strong> remplacements dans <strong>" . count($filesModified) . "</strong> fichiers</p>";
echo "<ul>";
echo "<li><code>articles_catalogue</code> → <code>articles</code></li>";
echo "<li><code>familles</code> → <code>familles_articles</code></li>";
echo "</ul>";
echo "</div>";

echo "<div class='alert alert-info'>";
echo "<h4>📝 ACTIONS MANUELLES RESTANTES</h4>";
echo "<ul>";
echo "<li>Remplacer <code>'famille'</code> (colonne texte) par <code>famille_id</code> (FK)</li>";
echo "<li>Vérifier module devis (si nécessaire, recréer tables)</li>";
echo "</ul>";
echo "</div>";

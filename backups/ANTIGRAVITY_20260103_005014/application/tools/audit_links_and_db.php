<?php
// tools/audit_links_and_db.php
// REVISED: Outputs to JSON file for reliability

$root = __DIR__ . '/../';
$files = glob($root . "*.php");
$report = [];

foreach ($files as $file) {
    $filename = basename($file);
    if ($filename == 'index.php') continue;
    
    $content = file_get_contents($file);
    
    // DB Check
    $has_db = (strpos($content, 'require_once \'db.php\'') !== false) || 
              (strpos($content, 'require_once "db.php"') !== false) || 
              (strpos($content, '$pdo') !== false);
    
    // Link Check
    preg_match_all('/href=["\']([^"\']+\.php)["\']/', $content, $matches);
    $links_found = array_unique($matches[1]);
    
    $broken_links = [];
    foreach ($links_found as $link) {
        $clean_link = explode('?', $link)[0];
        if (!file_exists($root . $clean_link)) {
            $broken_links[] = $link;
        }
    }
    
    $report[$filename] = [
        'has_db' => $has_db,
        'link_count' => count($links_found),
        'broken_items' => $broken_links
    ];
}

file_put_contents(__DIR__ . '/dist_audit.json', json_encode($report, JSON_PRETTY_PRINT));
echo "Audit completed. Results saved to dist_audit.json";
?>

<?php
// Cleanup Script
$targetDir = '_ARCHIVE_2025';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$files = glob('*.php');
$patterns = [
    '/^fix_.*\.php$/',
    '/^update_.*\.php$/',
    '/^migrate_.*\.php$/',
    '/^verify_.*\.php$/',
    '/^check_.*\.php$/',
    '/^tool_.*\.php$/', // Cleanup my own tools
    '/^audit_.*\.php$/',
    '/^analyse_.*\.php$/',
    '/^execute_.*\.php$/',
    '/^reinstall_.*\.php$/',
    '/^dumper\.php$/',
    '/^reinit_.*\.sql$/',
    '/^full_schema\.sql$/' // Maybe keep? No, user has dump.
];

$moved = [];

foreach ($files as $file) {
    // Skip self
    if ($file === 'tool_cleanup_files.php') continue;
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $file)) {
            if (rename($file, $targetDir . '/' . $file)) {
                $moved[] = $file;
            } else {
                echo "Failed to move $file<br>";
            }
            break; // Move once
        }
    }
}

echo "Moved " . count($moved) . " files to $targetDir:<br>";
print_r($moved);
?>

<?php
$path = __DIR__ . '/../images/header_doc.png';
$real = realpath($path);

echo "Dir: " . __DIR__ . "\n";
echo "Soft Path: " . $path . "\n";
echo "Real Path: " . $real . "\n";
echo "Exists: " . (file_exists($path) ? 'YES' : 'NO') . "\n";
echo "Size: " . (file_exists($path) ? filesize($path) : 'N/A') . "\n";
echo "Readable: " . (is_readable($path) ? 'YES' : 'NO') . "\n";

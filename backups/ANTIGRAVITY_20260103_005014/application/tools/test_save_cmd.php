<?php
// tools/test_save_cmd.php

// Simulate POST data
$_POST = [
    'imputation_type' => 'STOCK',
    'module_type' => 'PLIAGE',
    'fournisseur' => 'TEST_PLIAGE',
    'matiere' => 'ALU',
    'couleur' => 'RAL 7016',
    'quantite' => 5,
    'canvas_image' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==' // 1x1 pixel red dot
];

$_SERVER['REQUEST_METHOD'] = 'POST';

// Mock Session
session_start();
$_SESSION['user_id'] = 1;

// Capture ob to see if any spurious output occurs
ob_start();

// Include the target script
// We need to be careful about relative paths in the target script since we are including it from 'tools/'
// The target script expects to be in 'ajax/' and uses '../' to verify relative include paths.
// So we should chdir to 'ajax/' first.

chdir(__DIR__ . '/../ajax');
require 'save_commande_rapide.php';

$output = ob_get_clean();

echo "--- RAW OUTPUT START ---\n";
echo $output;
echo "\n--- RAW OUTPUT END ---\n";

<?php
// tools/test_pliage_stock.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'imputation_type' => 'STOCK',
    'module_type' => 'PLIAGE',
    'fournisseur' => 'TEST_PLIAGE_STOCK',
    'matiere' => 'ALU',
    'ral' => '7016', // Note: Form sends 'ral', not 'couleur'
    'quantite' => 5,
    'canvas_image' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg=='
];

// Mock Session
session_start();
$_SESSION['user_id'] = 1;

chdir(__DIR__ . '/../ajax');
require 'save_commande_rapide.php';

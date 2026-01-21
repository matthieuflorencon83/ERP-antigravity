<?php
// ajax/load_commande_module.php
// Charge le HTML d'un module de commande rapide

require_once '../auth.php'; // Securité
require_once '../functions.php';

// Check AJAX or at least POST/GET
$module = $_GET['module'] ?? '';

// Whitelist des modules pour sécurité (LFI protection)
$allowed_modules = [
    'vitrage' => 'views/modules/commande_rapide/form_vitrage.php',
    'pliage' => 'views/modules/commande_rapide/form_pliage.php',
    'profil' => 'views/modules/commande_rapide/form_profil.php',
    'panneaux' => 'views/modules/commande_rapide/form_panneaux.php',
    'quincaillerie' => 'views/modules/commande_rapide/form_quincaillerie.php',
    'libre' => 'views/modules/commande_rapide/form_libre.php'
];

header('Content-Type: text/html; charset=utf-8');

if (array_key_exists($module, $allowed_modules)) {
    $file = __DIR__ . '/../' . $allowed_modules[$module];
    if (file_exists($file)) {
        include $file;
    } else {
        echo "<div class='alert alert-warning'>Le formulaire pour ce module ($module) n'existe pas encore.</div>";
    }
} else {
    echo "<div class='alert alert-danger'>Module inconnu ou non autorisé.</div>";
}

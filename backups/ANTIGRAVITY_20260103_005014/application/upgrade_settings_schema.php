<?php
/**
 * upgrade_settings_schema.php
 * Adds missing configuration keys for Documents and UI
 */
require 'db.php';

try {
    $configs = [
        // GED Articles
        'chemin_notices' => [
            'desc' => 'Dossier de stockage des Notices (PDF)',
            'val' => 'uploads/ARTICLES/NOTICES/'
        ],
        'chemin_fiches_tech' => [
            'desc' => 'Dossier de stockage des Fiches Techniques (PDF)',
            'val' => 'uploads/ARTICLES/FT/'
        ],
        
        // GED Commandes
        'chemin_arc' => [
            'desc' => 'Dossier de stockage des Accusés de Réception (ARC)',
            'val' => 'uploads/ACHATS/ARC/'
        ],
        
        // UI & Thème
        'theme_default_mode' => [
            'desc' => 'Mode d\'affichage par défaut (light/dark)',
            'val' => 'light'
        ],
        'company_logo_path' => [
            'desc' => 'Chemin du logo entreprise (Header)',
            'val' => 'images/logo.png'
        ]
    ];

    foreach ($configs as $key => $data) {
        $stmt = $pdo->prepare("SELECT id FROM parametres_generaux WHERE cle_config = ?");
        $stmt->execute([$key]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO parametres_generaux (cle_config, valeur_config, description) VALUES (?, ?, ?)");
            $stmt->execute([$key, $data['val'], $data['desc']]);
            echo "INSERT: $key OK\n";
        } else {
            echo "SKIP: $key already exists.\n";
        }
    }
    
    // Create folders if they don't exist
    $folders = [
        'uploads/ARTICLES/NOTICES/',
        'uploads/ARTICLES/FT/',
        'uploads/ACHATS/ARC/'
    ];
    
    foreach($folders as $f) {
        if (!is_dir($f)) {
            mkdir($f, 0777, true);
            echo "MKDIR: $f OK\n";
        }
    }

    echo "Upgrade Completed.\n";

} catch (PDOException $e) {
    echo "SQL ERROR: " . $e->getMessage() . "\n";
}
?>

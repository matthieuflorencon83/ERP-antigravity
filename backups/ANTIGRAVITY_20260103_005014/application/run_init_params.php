<?php
require_once 'db.php';

$keys = [
    'path_arc_bdc_bl' => 'C:/Dropbox/Antigravity/ARC_BDC_BL',
    'path_bdc_fournisseur' => 'C:/Dropbox/Antigravity/BDC_Fournisseurs',
    'path_doc_tech' => 'C:/Dropbox/Antigravity/Doc_Technique',
    'path_notice' => 'C:/Dropbox/Antigravity/Notices'
];

foreach ($keys as $key => $default) {
    // Check if exists
    $check = $pdo->prepare("SELECT id FROM parametres_generaux WHERE cle_config = ?");
    $check->execute([$key]);
    if (!$check->fetch()) {
        // Insert
        $desc = "Chemin dossier " . str_replace('path_', '', $key);
        $ins = $pdo->prepare("INSERT INTO parametres_generaux (cle_config, valeur_config, description) VALUES (?, ?, ?)");
        $ins->execute([$key, $default, $desc]);
        echo "Inserted $key\n";
    } else {
        echo "Exists $key\n";
    }
}
echo "Done.";

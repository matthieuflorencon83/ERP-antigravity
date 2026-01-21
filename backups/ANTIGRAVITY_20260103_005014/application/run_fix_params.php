<?php
require_once 'db.php';

$corrections = [
    'path_arc_bdc_bl' => 'chemin_dropbox_arc',
    'path_bdc_fournisseur' => 'chemin_dropbox_bdc_fournisseur',
    'path_doc_tech' => 'chemin_dropbox_doc_tech',
    'path_notice' => 'chemin_dropbox_notice'
];

foreach ($corrections as $old => $new) {
    // Check if old exists
    $check = $pdo->prepare("SELECT id FROM parametres_generaux WHERE cle_config = ?");
    $check->execute([$old]);
    if ($check->fetch()) {
        $upd = $pdo->prepare("UPDATE parametres_generaux SET cle_config = ? WHERE cle_config = ?");
        $upd->execute([$new, $old]);
        echo "Renamed $old to $new\n";
    } else {
        // If old doesn't exist, check new
        $checkNew = $pdo->prepare("SELECT id FROM parametres_generaux WHERE cle_config = ?");
        $checkNew->execute([$new]);
        if (!$checkNew->fetch()) {
            // Insert default
            $desc = "Chemin Dropbox " . str_replace('chemin_dropbox_', '', $new);
            $ins = $pdo->prepare("INSERT INTO parametres_generaux (cle_config, valeur_config, description) VALUES (?, ?, ?)");
            $ins->execute([$new, 'C:/Dropbox/Antigravity/' . str_replace('chemin_dropbox_', '', $new), $desc]);
            echo "Inserted $new\n";
        } else {
            echo "Already OK: $new\n";
        }
    }
}
echo "Done.";

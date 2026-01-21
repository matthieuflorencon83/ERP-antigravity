<?php
/**
 * setup_final.php
 * Configuration finale pour les uploads et chemins
 */
require 'db.php';

try {
    // 1. Définir le chemin par défaut pour les Achats (BDC)
    // On utilise un chemin relatif local simple pour commencer : 'uploads/BDC/'
    $chemin_defaut = 'uploads/BDC/';
    
    // Création physique du dossier
    if (!is_dir($chemin_defaut)) {
        if (mkdir($chemin_defaut, 0777, true)) {
            echo "DIR: $chemin_defaut created.\n";
        } else {
            echo "DIR: Failed to create $chemin_defaut.\n";
        }
    } else {
        echo "DIR: $chemin_defaut exists.\n";
    }

    // 2. Insérer en base
    $cle = 'chemin_achats';
    $desc = 'Dossier de stockage des Bons de Commande (PDF)';
    
    $stmt = $pdo->prepare("SELECT id FROM parametres_generaux WHERE cle_config = ?");
    $stmt->execute([$cle]);
    
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO parametres_generaux (cle_config, valeur_config, description) VALUES (?, ?, ?)");
        $stmt->execute([$cle, $chemin_defaut, $desc]);
        echo "DB: Inserted chemin_achats\n";
    } else {
        echo "DB: chemin_achats already set.\n";
    }

} catch (PDOException $e) {
    echo "SQL ERROR: " . $e->getMessage() . "\n";
}
?>

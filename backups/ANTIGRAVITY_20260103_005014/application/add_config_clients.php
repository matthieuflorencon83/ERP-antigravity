<?php
require 'db.php';
try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO parametres_generaux (cle_config, valeur_config, description) VALUES ('chemin_clients', 'uploads/CLIENTS/', 'Dossier racine des archives Clients')");
    $stmt->execute();
    echo "Config Added Successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

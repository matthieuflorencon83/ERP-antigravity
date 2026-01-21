<?php
/**
 * setup_api.php
 * Configuration de la clé API Gemini (Version Corrigée)
 */
require 'db.php';

try {
    // 1. Vérifier la table (colonnes détectées: cle_config, valeur_config)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS parametres_generaux (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cle_config VARCHAR(50) UNIQUE,
            valeur_config TEXT,
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 2. Insérer/Mettre à jour la clé
    $apiKey = 'AIzaSyDYU5ISw_VSKxchQq0b3ZCP1BRhNsSpRfU';
    
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM parametres_generaux WHERE cle_config = 'api_key_gemini'");
    $stmt->execute();
    
    if ($stmt->fetch()) {
        $sql = "UPDATE parametres_generaux SET valeur_config = ? WHERE cle_config = 'api_key_gemini'";
    } else {
        $sql = "INSERT INTO parametres_generaux (cle_config, valeur_config, description) VALUES ('api_key_gemini', ?, 'Clé API pour Google Gemini 1.5 Flash')";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$apiKey]);
    
    echo "SUCCES : Clé API Gemini configurée dans la base de données (table: parametres_generaux | col: cle_config).\n";

} catch (PDOException $e) {
    die("ERREUR SQL : " . $e->getMessage());
}
?>

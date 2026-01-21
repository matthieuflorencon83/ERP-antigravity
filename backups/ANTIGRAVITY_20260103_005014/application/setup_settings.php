<?php
/**
 * setup_settings.php
 * Initialisation des paramètres Email et IA (si absents)
 */
require 'db.php';

try {
    // Liste des clés à vérifier/insérer
    $configs = [
        'api_key_gemini' => [
            'desc' => 'Clé API Google Gemini (IA)',
            'val' => 'AIzaSyDYU5ISw_VSKxchQq0b3ZCP1BRhNsSpRfU' // Already set but ensures update
        ],
        'email_expediteur' => [
            'desc' => 'Email utilisé pour l\'envoi des commandes',
            'val' => 'contact@votre-societe.com'
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
            // Optionnel : Mettre à jour la description si elle manque
             $stmt = $pdo->prepare("UPDATE parametres_generaux SET description = ? WHERE cle_config = ?");
             $stmt->execute([$data['desc'], $key]);
             echo "UPDATE: $key OK\n";
        }
    }
    echo "Setup Settings Completed.\n";

} catch (PDOException $e) {
    echo "SQL ERROR: " . $e->getMessage() . "\n";
}
?>

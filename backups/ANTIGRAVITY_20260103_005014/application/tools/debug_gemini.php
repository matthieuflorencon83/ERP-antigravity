<?php
require_once __DIR__ . '/../db.php';

// Récupération de la CLÉ API
$stmt = $pdo->query("SELECT valeur_config FROM parametres_generaux WHERE cle_config = 'api_key_gemini'");
$API_KEY = $stmt->fetchColumn();

if (!$API_KEY) {
    die("Clé API manquante.\n");
}

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $API_KEY;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo "Erreur cURL: " . curl_error($ch) . "\n";
} else {
    echo "HTTP Code: $http_code\n";
    $data = json_decode($response, true);
    if (isset($data['models'])) {
        echo "Modèles trouvés :\n";
        foreach ($data['models'] as $model) {
            // Affiche tous les noms pour être sûr
            echo "- " . $model['name'] . "\n";
        }
    } else {
        echo "Pas de modèles ou erreur:\n$response\n";
    }
}

curl_close($ch);
?>

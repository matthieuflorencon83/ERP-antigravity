<?php
// ai_parser.php - VERSION PRODUCTION (Moteur Python Actif)
header('Content-Type: application/json');
require_once 'auth.php'; // Securite
require_once 'db.php';

// 1. SÉCURITÉ & INIT (Géré par auth.php, mais on laisse le check JSON pour retour propre si besoin, 
// mais auth.php redirige HTML... Pour une API JSON, auth.php n'est pas idéal si on veut du JSON 401.
// Mais pour l'instant, faisons simple : si pas connecté, redirect login = erreur fetch JS.)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

// Récupération Clé API
$stmt = $pdo->query("SELECT valeur_config FROM parametres_generaux WHERE cle_config = 'api_key_gemini'");
$API_KEY = $stmt->fetchColumn();

if (!$API_KEY) {
    echo json_encode(['success' => false, 'message' => 'Clé API Gemini manquante (Voir Paramètres).']);
    exit;
}

$commande_id = $_POST['commande_id'] ?? null;
if (!$commande_id) {
    echo json_encode(['success' => false, 'message' => 'ID Commande manquant.']);
    exit;
}

// 2. RÉCUPÉRATION DU FICHIER
$stmt = $pdo->prepare("SELECT chemin_pdf_bdc FROM commandes_achats WHERE id = ?");
$stmt->execute([$commande_id]);
$chemin_pdf = $stmt->fetchColumn();

$texte_extrait = "";

if ($chemin_pdf && file_exists($chemin_pdf)) {
    // --- C'EST ICI QUE LA MAGIE OPÈRE ---
    
    // On construit le chemin absolu vers le script Python
    // __DIR__ donne le dossier actuel (racine du site)
    $script_path = __DIR__ . '/tools/extract_text.py';
    
    // Commande : python "chemin/vers/script.py" "chemin/vers/pdf"
    $cmd = "python " . escapeshellarg($script_path) . " " . escapeshellarg($chemin_pdf);
    
    // Exécution et récupération de la sortie (stdout)
    // 2>&1 permet de capturer aussi les erreurs
    $output = shell_exec($cmd . " 2>&1");
    
    // Python nous renvoie du JSON, on le décode
    $json_python = json_decode($output, true);
    
    if ($json_python && isset($json_python['success']) && $json_python['success'] === true) {
        $texte_extrait = $json_python['text'];
    } else {
        // En cas d'erreur Python, on loggue l'erreur mais on continue
        error_log("Erreur Python Antigravity : " . $output);
        $texte_extrait = "Erreur extraction texte : " . $output;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Fichier PDF introuvable sur le disque.']);
    exit;
}

// Si le texte est trop court (scan vide ou image), on prévient
if (strlen(trim($texte_extrait)) < 10) {
    echo json_encode(['success' => false, 'message' => 'Le PDF semble vide ou est une image scannée (non lisible par ce script).']);
    exit;
}

// 3. PRÉPARATION DU PROMPT (System Instruction)
$prompt = "
Tu es un assistant expert en comptabilité et gestion commerciale.
Analyse le texte brut ci-dessous extrait d'un document PDF (Bon de Commande, Facture ou Accusé de Réception ARC).

TA MISSION :
1. Identifier le TYPE de document (COMMANDE ou ARC).
2. Extraire les données structurées.
3. Corriger les erreurs OCR évidentes.

FORMAT DE SORTIE ATTENDU (JSON STRICT UNIQUEMENT) :
{
  \"type_document\": \"string\" ('COMMANDE' ou 'ARC'),
  \"numero_document\": \"string\",
  \"date_document\": \"YYYY-MM-DD\" (Date d'émission ou de réception),
  \"date_livraison_prevue\": \"YYYY-MM-DD\" (Pour les ARC, date de livraison annoncée. Sinon null),
  \"montant_total_ht\": float,
  \"nom_fournisseur\": \"string\",
  \"lignes_articles\": [
    {
      \"reference\": \"string\",
      \"designation\": \"string\",
      \"quantite\": float,
      \"prix_unitaire\": float
    }
  ]
}

RÈGLES IMPORTANTES :
1. JSON STRICT UNIQUEMENT.
2. Si type 'ARC' détecté (présence de 'Accusé de réception', 'ARC', 'Confirmation de commande'), cherche bien la date de livraison.
3. Ignore les lignes de totaux dans 'lignes_articles'.

TEXTE À ANALYSER :
\"$texte_extrait\"
";

// 4. APPEL GOOGLE GEMINI
// NOTE : On utilise 'gemini-flash-latest' car c'est celui qui est validé sur votre compte (1.5-flash standard était introuvable)
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . $API_KEY;

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['success' => false, 'message' => 'Erreur cURL : ' . curl_error($ch)]);
    exit;
}
curl_close($ch);

// 5. TRAITEMENT DE LA RÉPONSE
$json_google = json_decode($response, true);

if (isset($json_google['error'])) {
    echo json_encode(['success' => false, 'message' => 'Erreur Google API : ' . $json_google['error']['message']]);
    exit;
}

$reponse_texte = $json_google['candidates'][0]['content']['parts'][0]['text'] ?? '';
$reponse_texte = str_replace(['```json', '```'], '', $reponse_texte); // Nettoyage Markdown

$data_structuree = json_decode($reponse_texte, true);

if ($data_structuree) {
    // SUCCÈS TOTAL : On renvoie les données structurées au navigateur
    echo json_encode(['success' => true, 'data' => $data_structuree]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gemini a répondu mais le JSON est invalide.', 'raw' => $reponse_texte]);
}
?>

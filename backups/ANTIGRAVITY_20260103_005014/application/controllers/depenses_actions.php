<?php
/**
 * controllers/depenses_actions.php
 * Module : Actions pour les Dépenses / OCR
 */

header('Content-Type: application/json');
require_once '../db.php';
require_once '../functions.php';

// Init Session if needed
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

// --- 1. UPLOAD TEMP ET ANALYSE ---
if ($action === 'upload_analyze') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Erreur Upload']);
        exit;
    }

    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($ext !== 'pdf') {
        echo json_encode(['success' => false, 'message' => 'Format non supporté (PDF requis)']);
        exit;
    }

    // Dossier TEMP
    $tempDir = __DIR__ . '/../uploads/TEMP/';
    if (!is_dir($tempDir)) @mkdir($tempDir, 0777, true);

    $tempName = uniqid('ocr_', true) . '.pdf';
    $tempPath = $tempDir . $tempName;

    if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
        echo json_encode(['success' => false, 'message' => 'Impossible de déplacer le fichier']);
        exit;
    }

    // --- ANALYSE IA (COPIE ADAPTÉE DE AI_PARSER) ---
    // 1. EXTRACTION TEXTE (Python)
    $script_path = __DIR__ . '/../tools/extract_text.py';
    $cmd = "python " . escapeshellarg($script_path) . " " . escapeshellarg($tempPath);
    $output = shell_exec($cmd . " 2>&1");
    $json_python = json_decode($output, true);
    
    $texte_extrait = "";
    if ($json_python && ($json_python['success'] ?? false)) {
        $texte_extrait = $json_python['text'];
    } else {
        // Fallback ? Non, erreur critique pour l'OCR
        echo json_encode(['success' => false, 'message' => 'Erreur Lecture PDF (Python).']);
        exit;
    }

    if (strlen(trim($texte_extrait)) < 10) {
        echo json_encode(['success' => false, 'message' => 'Le PDF semble vide ou est une image scannée (non lisible).']);
        exit;
    }

    // 2. APPEL GEMINI
    // Récup Clé
    $stmt = $pdo->query("SELECT valeur_config FROM parametres_generaux WHERE cle_config = 'api_key_gemini'");
    $API_KEY = $stmt->fetchColumn();

    if (!$API_KEY) {
        echo json_encode(['success' => false, 'message' => 'Clé API Gemini manquante.']);
        exit;
    }

    $prompt = "
    Tu es un assistant comptable. Analyse ce texte de FACTURE FOURNISSEUR.
    Extrait : Nom Fournisseur, Date, Montant Total HT, Numéro Facture.
    Extrait aussi les lignes (Désignation, Qté, Prix Unitaire).
    
    FORMAT JSON STRICT :
    {
      \"nom_fournisseur\": \"string\",
      \"numero_document\": \"string\",
      \"date_document\": \"YYYY-MM-DD\",
      \"montant_total_ht\": float,
      \"lignes_articles\": [
        { \"designation\": \"string\", \"quantite\": float, \"prix_unitaire\": float }
      ]
    }
    
    TEXTE : \"$texte_extrait\"
    ";

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . $API_KEY;
    $data = [ "contents" => [ [ "parts" => [ ["text" => $prompt] ] ] ] ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    $response = curl_exec($ch);
    curl_close($ch);

    $json_google = json_decode($response, true);
    $reponse_texte = $json_google['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $reponse_texte = str_replace(['```json', '```'], '', $reponse_texte);
    
    $data_structuree = json_decode($reponse_texte, true);

    if ($data_structuree) {
        echo json_encode([
            'success' => true, 
            'data' => $data_structuree,
            'temp_file' => $tempName // On renvoie le nom pour le récupérer après validation
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Analyse IA échouée (JSON invalide).']);
    }
    exit;
}

// --- 2. RECHERCHE FOURNISSEUR ---
if ($action === 'search_fournisseur') {
    $q = $_GET['q'] ?? '';
    $stmt = $pdo->prepare("SELECT id, nom FROM fournisseurs WHERE nom LIKE ? LIMIT 10");
    $stmt->execute(["%$q%"]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// --- 3. CREATION DEPENSE ---
if ($action === 'create_expense') { // via JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    
    $fournisseur_id = $input['fournisseur_id'] ?? null;
    $fournisseur_nom = trim($input['fournisseur_nom'] ?? '');
    $temp_file = $input['temp_file'] ?? '';
    
    if (!$temp_file) {
        echo json_encode(['success' => false, 'message' => 'Fichier temporaire perdu.']);
        exit;
    }

    // GESTION FOURNISSEUR (Création si inexistant et pas d'ID)
    if (!$fournisseur_id && $fournisseur_nom) {
        // Vérif doublon exact
        $stmt = $pdo->prepare("SELECT id FROM fournisseurs WHERE nom = ?");
        $stmt->execute([$fournisseur_nom]);
        $existing = $stmt->fetchColumn();
        
        if ($existing) {
            $fournisseur_id = $existing;
        } else {
            // Création
            $stmt = $pdo->prepare("INSERT INTO fournisseurs (nom, email_commande, delai_habituel) VALUES (?, '', 'Standard')");
            $stmt->execute([$fournisseur_nom]);
            $fournisseur_id = $pdo->lastInsertId();
        }
    }

    if (!$fournisseur_id) {
         echo json_encode(['success' => false, 'message' => 'Fournisseur invalide.']);
         exit;
    }

    // CREATION COMMANDE (Statut Facturé directement ?)
    // On met statut 'Facturé' pour qu'elle compte dans le BI "Achats" ? 
    // Ou 'Livrée' ? Disons 'Facturé' pour indiquer qu'on a la facture.
    $ref_interne = 'DEP-' . date('ymd-Hi');
    
    $stmt = $pdo->prepare("INSERT INTO commandes_achats (fournisseur_id, ref_interne, date_commande, date_livraison_prevue, statut, montant_ht, numero_bl_fournisseur) VALUES (?, ?, ?, ?, 'Terminé', ?, ?)");
    $date_doc = !empty($input['date_document']) ? $input['date_document'] : date('Y-m-d');
    $montant = (float)$input['montant_ht'];
    $ref_doc = $input['numero_document'] ?? '';
    
    // Le statut 'Terminé' est mieux que 'Facturé' si ce dernier n'existe pas dans l'enum ?
    // Check enum... souvent c'est 'Brouillon', 'Commandée', 'Livrée', 'Terminé'.
    // On met 'Terminé' pour dire c'est clos.
    
    $stmt->execute([$fournisseur_id, $ref_interne, $date_doc, $date_doc, $montant, $ref_doc]);
    $cmd_id = $pdo->lastInsertId();

    // DEPLACEMENT DU FICHIER
    // On le met dans uploads/FOURNISSEURS/{Nom}/
    // Récup Nom Fournisseur propre
    $stmt = $pdo->prepare("SELECT nom FROM fournisseurs WHERE id = ?");
    $stmt->execute([$fournisseur_id]);
    $f_nom = $stmt->fetchColumn();
    
    // Logique safe_filename ? On fait simple.
    $f_clean = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $f_nom);
    $finalDir = __DIR__ . '/../uploads/FOURNISSEURS/' . $f_clean . '/';
    if (!is_dir($finalDir)) @mkdir($finalDir, 0777, true);
    
    $finalName = date('Ymd') . '_FACT_' . $ref_doc . '_' . uniqid() . '.pdf';
    $source = __DIR__ . '/../uploads/TEMP/' . $temp_file;
    $dest = $finalDir . $finalName;
    
    if (file_exists($source)) {
        rename($source, $dest);
        // Update DB
        // On le met dans chemin_pdf_bdc (c'est la "preuve" de la commande)
        $pdo->exec("UPDATE commandes_achats SET chemin_pdf_bdc = " . $pdo->quote('uploads/FOURNISSEURS/' . $f_clean . '/' . $finalName) . " WHERE id = $cmd_id");
    }

    // CREATION LIGNES ? (Pour avoir le détail BI par famille plus tard)
    // Pour l'instant on crée une ligne globale "Facture X" avec le montant total
    // Sauf si on a récupéré des lignes via IA ? Le front ne les renvoie pas encore.
    // Simplification : 1 ligne globale.
    $stmt = $pdo->prepare("INSERT INTO lignes_achat (commande_id, designation, qte_commandee, prix_unitaire_achat) VALUES (?, ?, 1, ?)");
    $stmt->execute([$cmd_id, "Facture " . $ref_doc, $montant]);

    echo json_encode(['success' => true, 'id' => $cmd_id]);
    exit;
}

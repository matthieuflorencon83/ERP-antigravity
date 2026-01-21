<?php
// ajax/save_besoin.php
require_once '../auth.php';
require_once '../db.php';
require_once '../core/BarOptimization.php';
require_once '../core/VerificationEngine.php';

header('Content-Type: application/json');

$response = ['success' => false, 'messages' => [], 'data' => []];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method Not Allowed");
    }

    // 1. Inputs
    $affaire_id = intval($_POST['affaire_id'] ?? 0);
    $designation = trim($_POST['designation'] ?? '');
    $quantite = intval($_POST['quantite'] ?? 1);
    $longueur = intval($_POST['longueur'] ?? 0);
    $article_id = !empty($_POST['article_id']) ? intval($_POST['article_id']) : null;
    $renfort = !empty($_POST['renfort_acier']) ? 1 : 0;
    
    // Type needed for rules (e.g. 'CHEVRON')
    $type_structurel = $_POST['type_structurel'] ?? ''; 

    if (!$affaire_id || empty($designation)) {
        throw new Exception("Champs obligatoires manquants (Affaire ou Désignation).");
    }

    // 2. Logic Engines
    $optResult = [];
    $alerts = [];
    
    // A. Calepinage
    if ($article_id) {
        // Fetch available lengths for this article
        $stmt = $pdo->prepare("SELECT longues_possibles_json FROM articles_catalogue WHERE id = ?"); // Typo risk check column
        // Using correct column from spec: longueurs_possibles_json
        $stmt = $pdo->prepare("SELECT longueurs_possibles_json FROM articles_catalogue WHERE id = ?");
        $stmt->execute([$article_id]);
        $json = $stmt->fetchColumn();
        
        $stock = $json ? json_decode($json, true) : [];
        if (!is_array($stock)) $stock = []; // Fallback
        
        // Add standard default lengths if empty? 6500 is standard
        if (empty($stock)) $stock = [6500];

        $optimizer = new BarOptimization();
        $optResult = $optimizer->optimize($longueur, $stock);
    }

    // B. Verification
    $verifier = new VerificationEngine();
    $alerts = $verifier->checkConsistency([
        'type' => $type_structurel,
        'longueur_brute' => $longueur,
        'renfort_acier' => $renfort,
        'affaire_id' => $affaire_id
    ]);

    // 3. Database Operation
    // Determine status based on alerts and optimization
    $statut = 'OPTIMISE';
    if (!empty($alerts)) $statut = 'A_VERIFIER'; // Warning raised
    if (isset($optResult['status']) && $optResult['status'] === 'ERROR') $statut = 'ERREUR_DIM';

    $barre_choisie = $optResult['recommended_bar'] ?? null;
    $taux_chute = $optResult['waste_percent'] ?? 0;

    $sql = "INSERT INTO besoins_lignes (
                affaire_id, 
                designation_besoin, 
                quantite_brute, 
                longueur_unitaire_brute_mm, 
                article_catalogue_id, 
                longueur_barre_choisie_mm, 
                taux_chute, 
                statut, 
                date_creation
            ) VALUES (
                :aff, :des, :qty, :len, :art, :bar, :chute, :stat, NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':aff' => $affaire_id,
        ':des' => $designation,
        ':qty' => $quantite,
        ':len' => $longueur,
        ':art' => $article_id,
        ':bar' => $barre_choisie,
        ':chute' => $taux_chute,
        ':stat' => $statut
    ]);
    
    $response['success'] = true;
    $response['id'] = $pdo->lastInsertId();
    $response['optimization'] = $optResult;
    $response['alerts'] = $alerts;
    $response['messages'][] = "Ligne ajoutée ($statut)";

} catch (Exception $e) {
    $response['messages'][] = $e->getMessage();
}

echo json_encode($response);

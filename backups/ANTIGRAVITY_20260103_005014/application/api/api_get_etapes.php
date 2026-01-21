<?php
// api/api_get_etapes.php
// Retourne les étapes de métrage selon la catégorie du produit
require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

try {
    $categorie = strtoupper($_GET['categorie'] ?? '');
    $type_id = (int)($_GET['type_id'] ?? 0);

    if (!$categorie && $type_id) {
        // Récupérer la catégorie depuis le type
        $stmt = $pdo->prepare("SELECT categorie FROM metrage_types WHERE id = ?");
        $stmt->execute([$type_id]);
        $type = $stmt->fetch();
        if ($type) {
            $categorie = strtoupper($type['categorie']);
        }
    }

    // Map catégorie to our etapes categories (match exactly as stored in DB)
    $catMap = [
        // Menuiserie -> FENETRE
        'MENUISERIE' => 'FENETRE',
        'FENETRE' => 'FENETRE',
        'FENÊTRE' => 'FENETRE',
        'PORTE-FENETRE' => 'FENETRE',
        'PORTE-FENÊTRE' => 'FENETRE',
        'COULISSANT' => 'FENETRE',
        'CHASSIS' => 'FENETRE',
        'CHÂSSIS' => 'FENETRE',
        
        // Volet
        'VOLET' => 'VOLET',
        'VOLET ROULANT' => 'VOLET',
        
        // Porte
        'PORTE' => 'PORTE',
        'PORTE ENTREE' => 'PORTE',
        'PORTE ENTRÉE' => 'PORTE',
        'PORTE SERVICE' => 'PORTE',
        
        // Garage
        'GARAGE' => 'GARAGE',
        
        // Portail
        'PORTAIL' => 'PORTAIL',
        
        // Pergola
        'PERGOLA' => 'PERGOLA',
        
        // Store
        'STORE' => 'STORE',
        
        // Veranda
        'VERANDA' => 'VERANDA',
        'VÉRANDA' => 'VERANDA',
        
        // Moustiquaire
        'MOUSTIQUAIRE' => 'MOUSTIQUAIRE',
        
        // TAV (Travaux Annexes)
        'TAV' => 'TAV',
    ];

    $mappedCat = $catMap[$categorie] ?? $categorie; // Use original if not mapped

    // Get common steps (categorie IS NULL) + category-specific steps
    $sql = "SELECT * FROM metrage_etapes 
            WHERE categorie IS NULL OR categorie = ?
            ORDER BY ordre ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$mappedCat]);
    $etapes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Parse JSON fields
    foreach ($etapes as &$e) {
        if (!empty($e['options_json'])) {
            $e['options'] = json_decode($e['options_json'], true);
        }
        if (!empty($e['champs_json'])) {
            $e['champs'] = json_decode($e['champs_json'], true);
        }
        if (!empty($e['condition_json'])) {
            $e['condition'] = json_decode($e['condition_json'], true);
        }
    }

    echo json_encode([
        'success' => true,
        'categorie' => $mappedCat,
        'etapes' => $etapes,
        'total' => count($etapes)
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

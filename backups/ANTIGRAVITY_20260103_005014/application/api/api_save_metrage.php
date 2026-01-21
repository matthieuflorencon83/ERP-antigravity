<?php
// api/api_save_metrage.php
// Endpoint de sauvegarde V3 (JSON Builder)

require_once '../auth.php';
require_once '../db.php';
require_once '../functions.php';
require_once '../classes/MetrageJsonBuilder.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée");
    }

    $metrage_id = (int)$_POST['metrage_id'];
    $ligne_id = (int)($_POST['ligne_id'] ?? 0);
    $type_id = (int)$_POST['type_id'];
    $fields = $_POST['fields'] ?? []; // The main data array

    // 1. Fetch Product Info
    $stmt = $pdo->prepare("SELECT * FROM metrage_types WHERE id = ?");
    $stmt->execute([$type_id]);
    $type = $stmt->fetch();
    if (!$type) throw new Exception("Type de produit invalide");

    // 2. Build JSON V3
    $builder = new MetrageJsonBuilder();
    
    // Meta
    $builder->setMeta($type['slug'], $type['nom'], $_SESSION['user_id'] ?? 0);
    
    // Auto-categorize fields based on keywords
    $builder->setDimensions($fields);
    $builder->setGeometry($fields); // New V3
    $builder->setQuality($fields);  // New V3
    $builder->setEnvironnement($fields);
    $builder->setSpecs($fields);
    $builder->setMedia($fields);
    $builder->setGeneric($fields); // Catch-all for PDF gen
    
    // Validation flags (passed via separate POST keys if handled by JS)
    // $builder->setValidation(...) 

    $json_content = $builder->build();

    // 3. Save to DB (Using only existing columns: intervention_id, metrage_type_id, localisation, donnees_json, notes_observateur, created_at)
    if ($ligne_id > 0) {
        // UPDATE existing line
        $sql = "UPDATE metrage_lignes SET 
                metrage_type_id = ?, 
                localisation = ?,
                donnees_json = ?
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $loc = $fields['localisation'] ?? '';
        $stmt->execute([$type_id, $loc, $json_content, $ligne_id]);
    } else {
        // INSERT new line
        $sql = "INSERT INTO metrage_lignes (intervention_id, metrage_type_id, localisation, donnees_json, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $loc = $fields['localisation'] ?? '';
        $stmt->execute([$metrage_id, $type_id, $loc, $json_content]);
        $ligne_id = $pdo->lastInsertId();
    }

    echo json_encode(['success' => true, 'id' => $ligne_id]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

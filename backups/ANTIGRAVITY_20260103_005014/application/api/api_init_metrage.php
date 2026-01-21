<?php
// api/api_init_metrage.php
require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée");
    }

    $affaire_id = isset($_POST['affaire_id']) && is_numeric($_POST['affaire_id']) ? (int)$_POST['affaire_id'] : null;

    // Création de l'intervention de métrage
    $sql = "INSERT INTO metrage_interventions (affaire_id, statut, created_at) VALUES (?, 'A_PLANIFIER', NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$affaire_id]);
    
    $id = $pdo->lastInsertId();

    echo json_encode(['success' => true, 'id' => $id]);

} catch (PDOException $e) {
    // Fallback: Si la table interventions a des colonnes différentes
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => "Erreur DB: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

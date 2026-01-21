<?php
// api/api_update_metrage_link.php
require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée");
    }

    $metrage_id = (int)$_POST['metrage_id'];
    $affaire_id = isset($_POST['affaire_id']) && is_numeric($_POST['affaire_id']) ? (int)$_POST['affaire_id'] : null;

    if (!$metrage_id) throw new Exception("ID Métrage manquant");

    // Update link
    $sql = "UPDATE interventions SET affaire_id = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$affaire_id, $metrage_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => "Erreur DB: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

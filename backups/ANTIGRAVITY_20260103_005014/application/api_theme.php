<?php
/**
 * api_theme.php
 * Endpoint AJAX pour sauvegarder la préférence de thème.
 * 
 * @project Antigravity
 * @version 1.0
 */

require 'db.php';

// Réponse JSON
header('Content-Type: application/json');

// Vérification simple (Simulation session active)
// En prod : if (!isset($_SESSION['user_id'])) ...

// Récupération du body JSON
$input = json_decode(file_get_contents("php://input"), true);
$new_theme = $input['theme'] ?? null;

if (!in_array($new_theme, ['light', 'dark'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thème invalide']);
    exit;
}

try {
    // 1. Mise à jour Session
    $_SESSION['theme'] = $new_theme;

    // 2. Mise à jour BDD (Simulation user ID 1)
    // À remplacer par $_SESSION['user_id'] quand le login sera actif
    $user_id = $_SESSION['user_id'] ?? 1;

    $stmt = $pdo->prepare("UPDATE utilisateurs SET theme_interface = ? WHERE id = ?");
    $stmt->execute([$new_theme, $user_id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

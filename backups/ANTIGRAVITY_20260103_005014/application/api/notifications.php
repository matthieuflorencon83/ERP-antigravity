<?php
/**
 * api/notifications.php
 * Endpoint JSON pour polling Ajax
 */
header('Content-Type: application/json');
require_once '../db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'poll';

if ($action === 'poll') {
    // Récupérer les notifs non lues créées dans les dernières minutes (pour Toast)
    // OU récupérer simplement le compte total
    try {
        // 1. Compte total non lu
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $count = $stmt->fetchColumn();

        // 2. Dernières notifs non lues (pour affichage dynamique)
        $since = $_GET['since'] ?? date('Y-m-d H:i:s', strtotime('-10 seconds'));
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 AND created_at > ? ORDER BY created_at DESC");
        $stmt->execute([$user_id, $since]);
        $new_notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'count' => $count,
            'new_notifications' => $new_notifs,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action === 'mark_read') {
    $id = $_POST['id'] ?? null;
    if ($id) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true]);
    }
}

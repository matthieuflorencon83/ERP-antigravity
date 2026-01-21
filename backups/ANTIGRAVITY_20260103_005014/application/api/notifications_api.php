<?php
/**
 * API Notifications
 * Gestion des notifications utilisateur en temps réel
 */

session_start();
require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

try {
    switch ($action) {
        case 'get_unread':
            // Récupérer les notifications non lues
            $stmt = $pdo->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? AND is_read = 0 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$user_id]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications
            ]);
            break;
            
        case 'mark_read':
            // Marquer une notification comme lue
            $id = $_POST['id'] ?? 0;
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$id, $user_id]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'mark_all_read':
            // Marquer toutes comme lues
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$user_id]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'count_unread':
            // Compter les non lues
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$user_id]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
            break;
            
        default:
            echo json_encode(['error' => 'Action invalide']);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

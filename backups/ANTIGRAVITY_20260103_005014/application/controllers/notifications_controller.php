<?php
/**
 * controllers/notifications_controller.php
 * Gestion métier des notifications utilisateur
 */

if (!isset($pdo)) {
    require_once __DIR__ . '/../db.php';
}

/**
 * Créer une notification pour un utilisateur
 */
function createNotification($pdo, $user_id, $title, $type = 'info', $message = '', $link = '#') {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link, is_read) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$user_id, $type, $title, $message, $link]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Récupérer les 5 dernières notifications pour l'affichage header
 */
function getRecentNotifications($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Compter les notifications non lues
 */
function countUnreadNotifications($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

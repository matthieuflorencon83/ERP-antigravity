<?php
session_start();
require_once 'db.php';

if(isset($_SESSION['user_id'])) {
    try {
        $stmtLog = $pdo->prepare("INSERT INTO access_logs (user_id, user_nom, event_type, ip_address, user_agent) VALUES (?, ?, 'LOGOUT', ?, ?)");
        $stmtLog->execute([$_SESSION['user_id'], $_SESSION['user_nom'] ?? '?', $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN', $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN']);
    } catch (Exception $e) {}
}

session_destroy();
header("Location: login.php");
exit;

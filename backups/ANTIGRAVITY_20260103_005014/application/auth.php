<?php
// auth.php - Middleware d'authentification

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// REFRESH ROLE (Auto-Fix for Permissions)
if (isset($_SESSION['user_id'])) {
    // On force la mise à jour du rôle depuis la BDD à chaque page
    // Cela évite les incohérences de session
    try {
        if (!isset($pdo)) { require_once __DIR__ . '/db.php'; }
        
        $stmtRole = $pdo->prepare("SELECT role FROM utilisateurs WHERE id = ?");
        $stmtRole->execute([$_SESSION['user_id']]);
        $realRole = $stmtRole->fetchColumn();
        
        if ($realRole) {
            $_SESSION['user_role'] = $realRole;
        }
    } catch (Exception $e) {
        // Silent fail, keep session value
    }
}

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    // Si tentative d'accès direct, on redirige vers login
    header("Location: login.php");
    exit;
}

<?php
/**
 * db.php
 * Gestion de la connexion Base de Données (PDO).
 * 
 * REFACTORED (Audit 2025-12-25):
 * - Environnement DEV/PROD configurable
 * - Gestion des erreurs robuste
 * - Session sécurisée
 * 
 * @project Antigravity
 * @version 3.0 (Hardened)
 */

// Charger la configuration centrale
require_once __DIR__ . '/config.php';

// ============================================
// CONNEXION PDO SÉCURISÉE
// ============================================
$dsn = sprintf(
    "mysql:host=%s;dbname=%s;charset=%s",
    DB_HOST,
    DB_NAME,
    DB_CHARSET
);

$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Sécurité: Vrais prepared statements
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
} catch (PDOException $e) {
    // En PROD: Message générique. En DEV: Détails.
    if (ANTIGRAVITY_ENV === 'PROD') {
        error_log("DB Connection Error: " . $e->getMessage());
        http_response_code(500);
        die("Erreur serveur. Veuillez réessayer plus tard.");
    } else {
        die("<h3>Erreur Critique - Base de données inaccessible</h3>
             <p>Vérifiez que MySQL est lancé (Laragon).</p>
             <pre>" . htmlspecialchars($e->getMessage()) . "</pre>");
    }
}

// ============================================
// GESTION SESSION SÉCURISÉE
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    // Paramètres de session sécurisés
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// ============================================
// AUTO-LOGIN DEV (UNIQUEMENT EN DÉVELOPPEMENT)
// ============================================
if (ANTIGRAVITY_ENV === 'DEV' && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Admin par défaut
    
    // Récupération du thème utilisateur
    try {
        $stmt = $pdo->prepare("SELECT theme_interface FROM utilisateurs WHERE id = ?");
        $stmt->execute([1]);
        $user = $stmt->fetch();
        $_SESSION['theme'] = $user['theme_interface'] ?? 'dark';
    } catch (Exception $e) {
        $_SESSION['theme'] = 'dark';
    }
}

// ============================================
// HELPERS GLOBAUX
// ============================================

/**
 * Vérifie si l'utilisateur est connecté
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Vérifie si l'environnement est en production
 */
function is_production(): bool {
    return ANTIGRAVITY_ENV === 'PROD';
}

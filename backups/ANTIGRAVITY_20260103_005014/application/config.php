<?php
/**
 * config.php
 * Fichier de configuration centralisé (S.S.O.T)
 * @version 1.0
 */

// ============================================
// 1. CONFIGURATION BASE DE DONNÉES
// ============================================
define('DB_HOST',    'localhost');
define('DB_NAME',    'antigravity');
define('DB_USER',    'root');
define('DB_PASS',    'root');
define('DB_CHARSET', 'utf8mb4');

// ============================================
// 2. CONFIGURATION SYSTÈME & FICHIERS
// ============================================
define('ANTIGRAVITY_ENV', 'DEV'); // 'DEV' ou 'PROD'
define('GED_ROOT',        'C:/ARTSALU'); // Racine du stockage fichiers

// ============================================
// 3. DETECTION DYNAMIQUE URL (Localtunnel / Ngrok / Localhost)
// ============================================
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
// Si on est à la racine ou sur un sous-dossier, adapter ici. Pour Laragon standard :
define('BASE_URL', $protocol . $domainName . '/antigravity');

// ============================================
// 3. PARAMÈTRES GLOBAUX
// ============================================
date_default_timezone_set('Europe/Paris');

// ============================================
// 4. OBSERVABILITÉ (LOGS)
// ============================================
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/antigravity_errors.log');
// Pour DEV uniquement : afficher les erreurs si besoin
if (ANTIGRAVITY_ENV === 'DEV') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
}

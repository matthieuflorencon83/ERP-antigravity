<?php
// config/mail_config.php
// Configuration du serveur email (Lecture depuis .env et base de données)

// Charger les variables d'environnement
require_once __DIR__ . '/env_loader.php';

// Utiliser le $pdo global si disponible
global $pdo;

$params = [];

// Essayer de charger les paramètres depuis la base
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT cle, valeur FROM parametres_generaux WHERE cle LIKE 'email_%'");
        while ($row = $stmt->fetch()) {
            $params[$row['cle']] = $row['valeur'];
        }
    } catch (PDOException $e) {
        // Ignorer les erreurs si la table n'existe pas encore
    }
}

// Configuration avec priorité : DB > .env > Défaut
$config = [
    'smtp' => [
        'host' => $params['email_smtp_host'] ?? env('EMAIL_SMTP_HOST', 'smtp.office365.com'),
        'port' => (int)($params['email_smtp_port'] ?? env('EMAIL_SMTP_PORT', 587)),
        'encryption' => $params['email_smtp_encryption'] ?? env('EMAIL_SMTP_ENCRYPTION', 'tls'),
        'username' => $params['email_smtp_username'] ?? env('EMAIL_SMTP_USERNAME', ''),
        'password' => $params['email_smtp_password'] ?? env('EMAIL_SMTP_PASSWORD', ''),
        'from_name' => $params['email_from_name'] ?? env('EMAIL_FROM_NAME', 'Antigravity'),
        'from_email' => $params['email_from_email'] ?? env('EMAIL_FROM_EMAIL', '')
    ],
    
    'imap' => [
        'host' => '{' . ($params['email_imap_host'] ?? env('EMAIL_IMAP_HOST', 'outlook.office365.com')) . ':' . ($params['email_imap_port'] ?? env('EMAIL_IMAP_PORT', '993')) . '/imap/ssl}INBOX',
        'username' => $params['email_smtp_username'] ?? env('EMAIL_SMTP_USERNAME', ''),
        'password' => $params['email_smtp_password'] ?? env('EMAIL_SMTP_PASSWORD', '')
    ],
    
    'cache' => [
        'enabled' => true,
        'ttl' => 300 // 5 minutes
    ],
    
    'limits' => [
        'max_emails_per_hour' => 50,
        'max_attachment_size' => 10485760 // 10 MB
    ]
];

return $config;

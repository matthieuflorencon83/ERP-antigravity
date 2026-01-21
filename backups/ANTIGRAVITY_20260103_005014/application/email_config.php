<?php
// email_config.php
// Configuration Simple (PHP Native Mail)
// Pour l'instant, on utilise le serveur SMTP local de Laragon (qui intercepte les mails)


// Chargement manuel de PHPMailer
require_once 'lib/PHPMailer/src/Exception.php';
require_once 'lib/PHPMailer/src/PHPMailer.php';
require_once 'lib/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuration SMTP (A remplacer par vos vrais identifiants)
define('SMTP_HOST', '127.0.0.1'); // Force IPv4 pour éviter les soucis de résolution localhost
define('SMTP_PORT', 25);      // Port standard Laragon (25 ou 1025)
define('SMTP_USER', '');
define('SMTP_PASS', '');

define('EMAIL_FROM', 'commandes@arts-alu.com');
define('EMAIL_FROM_NAME', 'Arts Alu - Service Achat');


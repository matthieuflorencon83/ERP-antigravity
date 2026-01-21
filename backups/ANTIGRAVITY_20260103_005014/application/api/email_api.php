<?php
// api/email_api.php
// API pour le module email

// SÉCURITÉ : Authentification obligatoire
session_start();
require_once '../auth.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Non autorisé. Authentification requise.']));
}

require_once '../db.php';
require_once '../classes/MailManager.php';
require_once '../classes/TemplateEngine.php';
require_once '../core/RateLimiter.php';

// Rate Limiting (100 requêtes par minute)
$rateLimiter = new RateLimiter($pdo, 100, 60);
if (!$rateLimiter->check($_SESSION['user_id'], 'email_api')) {
    $rateLimiter->block();
}

header('Content-Type: application/json');

$mailManager = new MailManager($pdo);
$templateEngine = new TemplateEngine($pdo);

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Lister les emails d'un dossier
            $folder = $_GET['folder'] ?? 'inbox';
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            if ($folder === 'inbox') {
                $emails = $mailManager->getInbox($limit, $offset);
            } elseif ($folder === 'sent') {
                $emails = $mailManager->getSentEmails($limit, $offset);
            } else {
                $emails = [];
            }
            
            echo json_encode($emails);
            break;
            
        case 'get':
            // Récupérer un email spécifique
            $emailId = (int)($_GET['id'] ?? 0);
            
            if ($emailId > 0) {
                $email = $mailManager->getEmail($emailId);
                echo json_encode($email);
            } else {
                echo json_encode(['error' => 'ID invalide']);
            }
            break;
            
        case 'send':
            // Envoyer un email
            $input = json_decode(file_get_contents('php://input'), true);
            
            // CSRF Protection
            if (!csrf_verify($input['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'CSRF token invalide']);
                exit;
            }
            
            $to = $input['to'] ?? '';
            $subject = $input['subject'] ?? '';
            $body = $input['body'] ?? '';
            $attachments = $input['attachments'] ?? [];
            $affaireId = $input['affaire_id'] ?? null;
            $clientId = $input['client_id'] ?? null;
            
            $result = $mailManager->sendEmail($to, $subject, $body, $attachments, $affaireId, $clientId);
            echo json_encode($result);
            break;
            
        case 'templates':
            // Lister les templates
            $categorie = $_GET['categorie'] ?? null;
            $templates = $templateEngine->getTemplates($categorie);
            echo json_encode($templates);
            break;
            
        case 'render_template':
            // Rendre un template avec variables
            $templateId = (int)($_GET['template_id'] ?? 0);
            $clientId = (int)($_GET['client_id'] ?? 0);
            $affaireId = (int)($_GET['affaire_id'] ?? 0);
            
            if ($affaireId > 0) {
                $result = $templateEngine->renderForAffaire($templateId, $affaireId);
            } elseif ($clientId > 0) {
                $result = $templateEngine->renderForClient($templateId, $clientId);
            } else {
                $result = ['error' => 'Client ou Affaire requis'];
            }
            
            echo json_encode($result);
            break;
            
        case 'client_emails':
            // Récupérer les emails d'un client
            $clientId = (int)($_GET['client_id'] ?? 0);
            $emails = $mailManager->getClientEmails($clientId);
            echo json_encode($emails);
            break;
            
        case 'affaire_emails':
            // Récupérer les emails d'une affaire
            $affaireId = (int)($_GET['affaire_id'] ?? 0);
            $emails = $mailManager->getAffaireEmails($affaireId);
            echo json_encode($emails);
            break;
            
        case 'get_template':
            // Récupérer un template
            $templateId = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE id = ?");
            $stmt->execute([$templateId]);
            $template = $stmt->fetch();
            
            if ($template) {
                echo json_encode([
                    'success' => true,
                    'sujet' => $template['sujet'],
                    'contenu' => $template['contenu']
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Template introuvable']);
            }
            break;
            
        default:
            echo json_encode(['error' => 'Action inconnue']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

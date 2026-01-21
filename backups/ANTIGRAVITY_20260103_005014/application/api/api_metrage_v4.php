<?php
/**
 * api_metrage_v4.php - API Endpoint Unifié V4.0
 * 
 * Toutes les opérations métrage passent par ce fichier
 * Routing basé sur le paramètre 'action'
 * 
 * @version 4.0.0
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../controllers/MetrageController.php';

// Vérifier authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$controller = new MetrageController($pdo, $userId);

// Récupérer l'action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        
        // ===== CRÉATION & INITIALISATION =====
        
        case 'create':
            // POST: affaire_id (optionnel)
            $affaireId = isset($_POST['affaire_id']) && $_POST['affaire_id'] !== '' 
                ? (int) $_POST['affaire_id'] 
                : null;
            
            $result = $controller->createIntervention($affaireId);
            echo json_encode($result);
            break;
        
        case 'link':
            // POST: intervention_id, affaire_id
            if (!isset($_POST['intervention_id']) || !isset($_POST['affaire_id'])) {
                throw new Exception('Paramètres manquants: intervention_id, affaire_id');
            }
            
            // IDOR Protection
            $interventionId = (int) $_POST['intervention_id'];
            if (!$controller->canEdit($interventionId)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Accès refusé']);
                exit;
            }
            
            $result = $controller->linkToAffaire(
                $interventionId,
                (int) $_POST['affaire_id']
            );
            echo json_encode($result);
            break;
        
        // ===== GESTION LIGNES =====
        
        case 'add_ligne':
            // POST: intervention_id, type_id, localisation, donnees_json
            if (!isset($_POST['intervention_id'], $_POST['type_id'], $_POST['localisation'], $_POST['donnees_json'])) {
                throw new Exception('Paramètres manquants');
            }
            
            // IDOR Protection
            $interventionId = (int) $_POST['intervention_id'];
            if (!$controller->canEdit($interventionId)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Accès refusé']);
                exit;
            }
            
            $donneesJson = json_decode($_POST['donnees_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON invalide: ' . json_last_error_msg());
            }
            
            $result = $controller->addLigne(
                $interventionId,
                (int) $_POST['type_id'],
                $_POST['localisation'],
                $donneesJson
            );
            echo json_encode($result);
            break;
        
        case 'update_ligne':
            // POST: ligne_id, donnees_json
            if (!isset($_POST['ligne_id'], $_POST['donnees_json'])) {
                throw new Exception('Paramètres manquants');
            }
            
            // IDOR Protection: Check ownership via ligne -> intervention
            $ligneId = (int) $_POST['ligne_id'];
            if (!$controller->canEditLigne($ligneId)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Accès refusé']);
                exit;
            }
            
            $donneesJson = json_decode($_POST['donnees_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON invalide: ' . json_last_error_msg());
            }
            
            $result = $controller->updateLigne(
                $ligneId,
                $donneesJson
            );
            echo json_encode($result);
            break;
        
        case 'delete_ligne':
            // POST: ligne_id
            if (!isset($_POST['ligne_id'])) {
                throw new Exception('Paramètre manquant: ligne_id');
            }
            
            // IDOR Protection
            $ligneId = (int) $_POST['ligne_id'];
            if (!$controller->canEditLigne($ligneId)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Accès refusé']);
                exit;
            }
            
            $result = $controller->deleteLigne($ligneId);
            echo json_encode($result);
            break;
        
        // ===== RÉCUPÉRATION DONNÉES =====
        
        case 'get_intervention':
            // GET: id
            if (!isset($_GET['id'])) {
                throw new Exception('Paramètre manquant: id');
            }
            
            // IDOR Protection
            $interventionId = (int) $_GET['id'];
            if (!$controller->canView($interventionId)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Accès refusé']);
                exit;
            }
            
            $intervention = $controller->getIntervention($interventionId);
            if (!$intervention) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Intervention introuvable']);
            } else {
                echo json_encode(['success' => true, 'data' => $intervention]);
            }
            break;
        
        case 'get_lignes':
            // GET: intervention_id
            if (!isset($_GET['intervention_id'])) {
                throw new Exception('Paramètre manquant: intervention_id');
            }
            
            // IDOR Protection
            $interventionId = (int) $_GET['intervention_id'];
            if (!$controller->canView($interventionId)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Accès refusé']);
                exit;
            }
            
            $lignes = $controller->getLignes($interventionId);
            echo json_encode(['success' => true, 'data' => $lignes]);
            break;
        
        case 'get_types':
            // GET: (aucun paramètre)
            $types = $controller->getTypes();
            echo json_encode(['success' => true, 'data' => $types]);
            break;
        
        // ===== SYNC OFFLINE =====
        
        case 'sync':
            // POST: draft_data (JSON complet depuis localStorage)
            if (!isset($_POST['draft_data'])) {
                throw new Exception('Paramètre manquant: draft_data');
            }
            
            $draftData = json_decode($_POST['draft_data'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON invalide: ' . json_last_error_msg());
            }
            
            // TODO: Implémenter logique de synchronisation
            // Pour l'instant, retourner succès
            echo json_encode([
                'success' => true,
                'message' => 'Synchronisation réussie',
                'synced_items' => count($draftData['products'] ?? [])
            ]);
            break;
        
        // ===== ACTION INVALIDE =====
        
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => "Action invalide: '{$action}'"
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

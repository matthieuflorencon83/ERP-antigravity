<?php
/**
 * API COCKPIT MÉTRAGE V2.0 (Refonte Complète)
 * Protocole Antigravity : PDO, Sécurité Stricte, Pas d'hallucination.
 * 
 * Schéma de BDD respecté :
 * - metrage_interventions (id, affaire_id, technicien_id, statut, gps_lon...)
 * - affaires (id, statut='Devis'/'Signé')
 * - clients (id, gps_lng...)
 */

require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Protection CSRF pour les méthodes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Note: Dans un contexte API pur, le CSRF peut être complexe à gérer si le token n'est pas passé.
    // Pour l'instant, on s'appuie sur la session 'auth.php'.
}

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        
        // -----------------------------------------------------------------------
        // 1. RÉCUPÉRER LES INTERVENTIONS (POUR LE KANBAN & CARTE)
        // -----------------------------------------------------------------------
        case 'get_tasks':
            $sql = "
                SELECT 
                    mi.id, 
                    mi.statut, 
                    mi.date_prevue, 
                    mi.technicien_id, 
                    mi.gps_lat, 
                    mi.gps_lon,
                    a.nom_affaire, 
                    a.numero_prodevis,
                    c.nom_principal as client_nom, 
                    c.ville as client_ville, 
                    c.telephone_fixe,
                    c.telephone_mobile,
                    COALESCE(u.nom_complet, 'Non assigné') as technicien_nom
                FROM metrage_interventions mi
                JOIN affaires a ON mi.affaire_id = a.id
                JOIN clients c ON a.client_id = c.id
                LEFT JOIN utilisateurs u ON mi.technicien_id = u.id
                ORDER BY mi.date_prevue ASC
            ";
            
            $stmt = $pdo->query($sql);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Post-traitement pour formatage
            foreach ($tasks as &$task) {
                // Gestion lat/long : Si pas précisé dans intervention, prendre client ? 
                // Pour l'instant on garde ce qui est en base intervention.
                
                // Formatage date
                if ($task['date_prevue']) {
                    $task['date_fmt'] = date('d/m H:i', strtotime($task['date_prevue']));
                } else {
                    $task['date_fmt'] = 'À planifier';
                }
            }

            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;

        // -----------------------------------------------------------------------
        // 2. RÉCUPÉRER LES AFFAIRES CANDIDATES (POUR LE SELECT "NOUVEAU")
        // -----------------------------------------------------------------------
        case 'get_affaires_sans_metrage':
            // Règle Métier : Affaires 'Devis' ou 'Signé' qui n'ont PAS d'entrée dans metrage_interventions
            $sql = "
                SELECT 
                    a.id, 
                    a.nom_affaire, 
                    c.nom_principal as client
                FROM affaires a
                JOIN clients c ON a.client_id = c.id
                LEFT JOIN metrage_interventions mi ON a.id = mi.affaire_id
                WHERE (a.statut = 'Devis' OR a.statut = 'Signé')
                AND mi.id IS NULL
                ORDER BY a.date_creation DESC
                LIMIT 50
            ";
            
            $stmt = $pdo->query($sql);
            $affaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'affaires' => $affaires]);
            break;

        // -----------------------------------------------------------------------
        // 3. RÉCUPÉRER LES TECHNICIENS (POUR LE SELECT)
        // -----------------------------------------------------------------------
        case 'get_techniciens':
            // On prend tous les utilisateurs pour l'instant (ou filtrer par rôle si colonne role existe)
            // Audit a montré : role enum('ADMIN','POSEUR','SECRETAIRE')
            $sql = "SELECT id, nom_complet as nom FROM utilisateurs WHERE role IN ('ADMIN', 'POSEUR') ORDER BY nom_complet";
            $stmt = $pdo->query($sql);
            echo json_encode(['success' => true, 'techniciens' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // -----------------------------------------------------------------------
        // 4. CRÉER UNE INTERVENTION (ACTION POST)
        // -----------------------------------------------------------------------
        case 'create_intervention':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode POST requise");
            }

            $affaire_id = filter_input(INPUT_POST, 'affaire_id', FILTER_VALIDATE_INT);
            $technicien_id = filter_input(INPUT_POST, 'technicien_id', FILTER_VALIDATE_INT); // Peut être null
            $date_prevue = $_POST['date_prevue'] ?? null; // datetime-local string

            if (!$affaire_id) {
                throw new Exception("L'affaire est obligatoire.");
            }

            // Vérifier existence affaire
            $stmtCheck = $pdo->prepare("SELECT id, client_id FROM affaires WHERE id = ?");
            $stmtCheck->execute([$affaire_id]);
            $affaire = $stmtCheck->fetch();
            if (!$affaire) {
                throw new Exception("Affaire introuvable.");
            }

            // Récupérer infos GPS du client pour initialiser l'intervention
            $stmtClient = $pdo->prepare("SELECT gps_lat, gps_lng FROM clients WHERE id = ?");
            $stmtClient->execute([$affaire['client_id']]);
            $clientInfos = $stmtClient->fetch();
            
            $gps_lat = $clientInfos['gps_lat'] ?? null;
            $gps_lon = $clientInfos['gps_lng'] ?? null; // Attention audit : gps_lng dans clients, gps_lon dans interventions

            // Insertion
            $sqlInsert = "
                (affaire_id, technicien_id, date_prevue, statut, gps_lat, gps_lon, created_at)
                VALUES 
                (:affaire_id, :technicien_id, :date_prevue, :initial_status, :gps_lat, :gps_lon, NOW())
            ";
            
            // Si une date est définie, statut = PLANIFIE, sinon A_PLANIFIER
            $initial_status = ($date_prevue && $date_prevue !== '0000-00-00 00:00:00') ? 'PLANIFIE' : 'A_PLANIFIER';

            $stmt = $pdo->prepare($sqlInsert);
            $stmt->execute([
                ':affaire_id' => $affaire_id,
                ':technicien_id' => $technicien_id ?: null, // null si 0 ou vide
                ':date_prevue' => $date_prevue ?: null,
                ':initial_status' => $initial_status,
                ':gps_lat' => $gps_lat,
                ':gps_lon' => $gps_lon
            ]);

            echo json_encode(['success' => true, 'message' => 'Intervention créée']);
            break;

        default:
            throw new Exception("Action inconnue : " . $action);
    }

} catch (Exception $e) {
    http_response_code(500); // Server Error
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

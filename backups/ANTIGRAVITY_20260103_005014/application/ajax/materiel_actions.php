<?php
// ajax/materiel_actions.php
require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée");
    }

    $action = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        throw new Exception("Session expirée");
    }

    switch ($action) {
        case 'add':
            $affaire_id = intval($_POST['affaire_id']);
            $designation = trim($_POST['designation']);
            $quantite = intval($_POST['quantite']);
            $unite = trim($_POST['unite']);
            $priorite = $_POST['priorite']; // BASSE, NORMALE, HAUTE, URGENTE
            $statut = $_POST['statut']; // A_PREVOIR, COMMANDE, SUR_SITE, RETOURNE
            $commentaire = trim($_POST['commentaire'] ?? '');

            if (empty($designation)) {
                throw new Exception("La désignation est obligatoire");
            }

            $stmt = $pdo->prepare("
                INSERT INTO affaires_materiel 
                (affaire_id, designation, quantite, unite, priorite, statut, commentaire, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$affaire_id, $designation, $quantite, $unite, $priorite, $statut, $commentaire, $user_id]);
            
            $response['success'] = true;
            $response['message'] = "Matériel ajouté avec succès";
            break;

        case 'update':
            $id = intval($_POST['id']);
            $designation = trim($_POST['designation']);
            $quantite = intval($_POST['quantite']);
            $unite = trim($_POST['unite']);
            $priorite = $_POST['priorite'];
            $statut = $_POST['statut'];
            $commentaire = trim($_POST['commentaire'] ?? '');

            if (empty($designation)) {
                throw new Exception("La désignation est obligatoire");
            }

            $stmt = $pdo->prepare("
                UPDATE affaires_materiel 
                SET designation = ?, quantite = ?, unite = ?, priorite = ?, statut = ?, commentaire = ?
                WHERE id = ?
            ");
            $stmt->execute([$designation, $quantite, $unite, $priorite, $statut, $commentaire, $id]);

            $response['success'] = true;
            $response['message'] = "Matériel mis à jour";
            break;

        case 'delete':
            $id = intval($_POST['id']);
            
            $stmt = $pdo->prepare("DELETE FROM affaires_materiel WHERE id = ?");
            $stmt->execute([$id]);

            $response['success'] = true;
            $response['message'] = "Matériel supprimé";
            break;
            
        case 'change_status':
            $id = intval($_POST['id']);
            $statut = $_POST['statut'];
            
            $stmt = $pdo->prepare("UPDATE affaires_materiel SET statut = ? WHERE id = ?");
            $stmt->execute([$statut, $id]);
            
            $response['success'] = true;
            $response['message'] = "Statut mis à jour";
            break;

        case 'get':
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("SELECT * FROM affaires_materiel WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($item) {
                $response['success'] = true;
                $response['data'] = $item;
            } else {
                throw new Exception("Matériel introuvable");
            }
            break;

        default:
            throw new Exception("Action inconnue: $action");
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

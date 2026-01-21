<?php
// affaires_actions.php
require_once 'auth.php';
require_once 'db.php';
session_start();

$action = $_REQUEST['action'] ?? '';
$id = (int)($_REQUEST['id'] ?? 0);

if (!$id) {
    $_SESSION['error'] = "ID Affaire manquant.";
    header("Location: affaires_liste.php");
    exit;
}

switch ($action) {
    case 'delete':
        // Vérification des dépendances (Commandes, Devis...)
        // Si des commandes existent, on empêche la suppression pour intégrité
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes_achats WHERE affaire_id = ?");
        $stmt->execute([$id]);
        $nb_cmd = $stmt->fetchColumn();

        if ($nb_cmd > 0) {
            $_SESSION['error'] = "Impossible de supprimer cette affaire : Elle contient $nb_cmd commande(s).";
            header("Location: affaires_liste.php");
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM affaires WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Affaire supprimée avec succès.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la suppression : " . $e->getMessage();
        }
        
        header("Location: affaires_liste.php");
        exit;
        break;

    default:
        header("Location: affaires_liste.php");
        exit;
}

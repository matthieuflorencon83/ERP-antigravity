<?php
// catalogue_actions.php - Actions CRUD pour le catalogue
session_start();
require_once 'db.php';
require_once 'functions.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id = $_GET['id'] ?? $_POST['id'] ?? 0;

switch($action) {
    case 'delete':
        if (!$id) {
            header('Location: catalogue_liste.php?error=noid');
            exit;
        }

        try {
            // Tentative de suppression direct
            $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Location: catalogue_liste.php?deleted=1');
            exit;

        } catch (PDOException $e) {
            // Échec (probablement FK Constraint)
            // On peut logger l'erreur
            error_log("Erreur suppression article $id : " . $e->getMessage());
            header('Location: catalogue_liste.php?error=constraint');
            exit;
        }
        break;

    default:
        // Action inconnue
        header('Location: catalogue_liste.php');
        exit;
}

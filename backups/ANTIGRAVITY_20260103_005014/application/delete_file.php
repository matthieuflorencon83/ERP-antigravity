<?php
/**
 * delete_file.php
 * Supprime un fichier attaché à une commande (BDC ou ARC)
 */
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php'; // Pour les logs si besoin

if (!isset($_SESSION['user_id'])) {
    die("Non autorisé.");
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';

if ($id === 0 || !in_array($type, ['bdc', 'arc'])) {
    die("Paramètres invalides.");
}

// 1. Récupérer le chemin
$col = ($type === 'arc') ? 'chemin_pdf_arc' : 'chemin_pdf_bdc';
$stmt = $pdo->prepare("SELECT $col FROM commandes_achats WHERE id = ?");
$stmt->execute([$id]);
$path = $stmt->fetchColumn();

// 2. Supprimer le fichier physique
if ($path && file_exists($path)) {
    unlink($path);
}

// 3. Update BDD
$stmt = $pdo->prepare("UPDATE commandes_achats SET $col = NULL WHERE id = ?");
$stmt->execute([$id]);

// 4. Redirection
header("Location: commandes_detail.php?id=$id");
exit;

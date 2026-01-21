<?php
// fournisseur_actions.php
require_once 'auth.php';
require_once 'db.php';
session_start();

// Fonctions de validation
function valider_email($email) {
    if (empty($email)) return true; // Optionnel
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function valider_telephone($tel) {
    if (empty($tel)) return true; // Optionnel
    // Format français: 10 chiffres, peut contenir espaces/points/tirets
    $tel_clean = preg_replace('/[\s.-]/', '', $tel);
    return preg_match('/^(?:(?:\+|00)33|0)[1-9]\d{8}$/', $tel_clean);
}

function valider_mobile($mobile) {
    if (empty($mobile)) return true; // Optionnel
    $mobile_clean = preg_replace('/[\s.-]/', '', $mobile);
    return preg_match('/^(?:(?:\+|00)33|0)[67]\d{8}$/', $mobile_clean);
}

function valider_code_postal($cp) {
    if (empty($cp)) return true; // Optionnel
    return preg_match('/^[0-9]{5}$/', $cp);
}

$action = $_REQUEST['action'] ?? '';
$fou_id = $_REQUEST['fournisseur_id'] ?? ($_REQUEST['fou'] ?? 0);

if (!$fou_id) {
    $_SESSION['error'] = "ID Fournisseur manquant.";
    header("Location: fournisseurs_liste.php");
    exit;
}

switch ($action) {
    case 'add_contact':
        // VALIDATION (silencieuse, HTML5 fait le travail)
        if (!valider_email($_POST['email']) || 
            !valider_telephone($_POST['telephone']) || 
            !valider_mobile($_POST['mobile'])) {
            // Ne rien faire, HTML5 a déjà bloqué
            header("Location: fournisseurs_detail.php?id=$fou_id");
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO fournisseur_contacts (fournisseur_id, nom, role, email, telephone_fixe, telephone_mobile) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $fou_id,
            mb_strtoupper($_POST['nom'], 'UTF-8'), // NOM UPPERCASE
            $_POST['role'],
            $_POST['email'],
            $_POST['telephone'],
            $_POST['mobile']
        ]);
        break;

    case 'del_contact':
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM fournisseur_contacts WHERE id = ? AND fournisseur_id = ?");
        $stmt->execute([$id, $fou_id]);
        break;

    case 'add_address':
        // VALIDATION (silencieuse)
        if (!valider_code_postal($_POST['code_postal']) || 
            !valider_telephone($_POST['telephone'])) {
            header("Location: fournisseurs_detail.php?id=$fou_id");
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO fournisseur_adresses (fournisseur_id, type_adresse, adresse, code_postal, ville, contact_sur_place, telephone) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $fou_id,
            $_POST['type_adresse'],
            $_POST['adresse'],
            $_POST['code_postal'],
            $_POST['ville'],
            $_POST['contact_sur_place'],
            $_POST['telephone']
        ]);
        break;

    case 'del_address':
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM fournisseur_adresses WHERE id = ? AND fournisseur_id = ?");
        $stmt->execute([$id, $fou_id]);
        break;

    case 'delete':
        $id = $_GET['id'] ?? 0;
        // Vérification basique ou contrainte FK gérée par MySQL
        try {
            $stmt = $pdo->prepare("DELETE FROM fournisseurs WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: fournisseurs_liste.php?deleted=1");
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = "Impossible de supprimer ce fournisseur (peut-être lié à des commandes).";
            header("Location: fournisseurs_liste.php?error=1");
            exit;
        }
        break;
}

// Redirect back
header("Location: fournisseurs_detail.php?id=$fou_id");
exit;


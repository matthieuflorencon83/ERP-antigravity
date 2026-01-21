<?php
// client_actions.php
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
$cli_id = $_REQUEST['client_id'] ?? ($_REQUEST['cli'] ?? 0);

if (!$cli_id) {
    $_SESSION['error'] = "ID Client manquant.";
    header("Location: clients_liste.php");
    exit;
}

switch ($action) {
    case 'add_contact':
        // VALIDATION (silencieuse, HTML5 fait le travail)
        if (!valider_email($_POST['email']) || 
            !valider_telephone($_POST['telephone']) || 
            !valider_mobile($_POST['mobile'])) {
            // Ne rien faire, HTML5 a déjà bloqué
            header("Location: clients_fiche.php?id=$cli_id&tab=contacts");
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO client_contacts (client_id, nom, role, email, telephone_fixe, telephone_mobile) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $cli_id,
            mb_strtoupper($_POST['nom'], 'UTF-8'), // NOM UPPERCASE
            $_POST['role'],
            $_POST['email'],
            $_POST['telephone'], // Fixe
            $_POST['mobile']     // Mobile
        ]);
        header("Location: clients_fiche.php?id=$cli_id&tab=contacts&success=contact_added");
        exit;
        break;

    case 'del_contact':
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM client_contacts WHERE id = ? AND client_id = ?");
        $stmt->execute([$id, $cli_id]);
        header("Location: clients_fiche.php?id=$cli_id&tab=contacts&success=contact_deleted");
        exit;
        break;

    case 'add_address':
        // VALIDATION (silencieuse)
        if (!valider_code_postal($_POST['code_postal']) || 
            !valider_telephone($_POST['telephone'])) {
            header("Location: clients_fiche.php?id=$cli_id&tab=adresses");
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO client_adresses (client_id, type_adresse, adresse, code_postal, ville, contact_sur_place, telephone, commentaires) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $cli_id,
            $_POST['type_adresse'],
            $_POST['adresse'],
            $_POST['code_postal'],
            $_POST['ville'],
            $_POST['contact_sur_place'],
            $_POST['telephone'],
            $_POST['commentaires']
        ]);
        header("Location: clients_fiche.php?id=$cli_id&tab=adresses&success=address_added");
        exit;
        break;

    case 'del_address':
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM client_adresses WHERE id = ? AND client_id = ?");
        $stmt->execute([$id, $cli_id]);
        header("Location: clients_fiche.php?id=$cli_id&tab=adresses&success=address_deleted");
        exit;
        break;
}

// Redirect back default
header("Location: clients_fiche.php?id=$cli_id");
exit;

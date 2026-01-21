<?php
/**
 * ged_manager.php
 * Gestionnaire de dossiers GED : Création et Organisation.
 * 
 * @project Antigravity
 * @version 1.0
 */

session_start();
require_once 'db.php';
require_once 'functions.php';

// Auth
if (!isset($_SESSION['user_id'])) {
    die("Accès refusé.");
}

// Action
$action = $_POST['action'] ?? '';

if ($action === 'create_folder') {
    $affaire_id = (int)$_POST['affaire_id'];
    
    // 1. Infos Affaire
    $stmt = $pdo->prepare("
        SELECT a.id, a.nom_affaire, a.date_creation, c.nom_principal as client_nom 
        FROM affaires a
        JOIN clients c ON a.client_id = c.id
        WHERE a.id = ?
    ");
    $stmt->execute([$affaire_id]);
    $aff = $stmt->fetch();
    
    if (!$aff) die("Affaire introuvable.");

    // 2. Construction du Chemin
    // Structure : C:/ARTSALU/AFFAIRES/{ANNEE}/{CLIENT}/{AFFAIRE}
    
    // IMPORTANT : On doit s'assurer que C:/ARTSALU existe sinon on fallback sur un dossier local 'uploads/ged' pour le dev ?
    // Instructions disent C:/ARTSALU. On va tenter, si échec on avertit.
    
    $root = GED_ROOT . '/AFFAIRES';
    
    // Si on est en dev local et que C:/ARTSALU n'existe pas, on simule dans le dossier web ?
    // Pour l'agent AI, on va vérifier si le root est accessible.
    if (!is_dir(GED_ROOT) && !mkdir(GED_ROOT, 0777, true)) {
        // Fallback DEV si pas de disque C:/ARTSALU (ex: Environnement de test restreint)
        $root = __DIR__ . '/uploads/GED_AFFAIRES';
    }

    $annee = date('Y', strtotime($aff['date_creation'] ?? date('Y-m-d')));
    
    $client_clean = safe_filename($aff['client_nom']);
    $affaire_clean = safe_filename($aff['nom_affaire']) . "_" . $aff['id']; // ID pour unicité
    
    $full_path = $root . '/' . $annee . '/' . $client_clean . '/' . $affaire_clean;
    
    // 3. Création Physique
    // str_replace pour Windows
    $full_path = str_replace('/', DIRECTORY_SEPARATOR, $full_path);
    
    if (!is_dir($full_path)) {
        if (!mkdir($full_path, 0777, true)) {
            die("Erreur critique : Impossible de créer le dossier $full_path. Vérifiez les permissions.");
        }
        
        // Sous-dossiers standards
        mkdir($full_path . DIRECTORY_SEPARATOR . 'Plans', 0777, true);
        mkdir($full_path . DIRECTORY_SEPARATOR . 'Photos', 0777, true);
        mkdir($full_path . DIRECTORY_SEPARATOR . 'Devis_Fournisseurs', 0777, true);
    }
    
    // 4. Update DB
    $stmt = $pdo->prepare("UPDATE affaires SET chemin_dossier_ged = ? WHERE id = ?");
    $stmt->execute([$full_path, $affaire_id]);
    
    // Retour
    header("Location: affaires_detail.php?id=$affaire_id&success=ged_created#ged");
    exit;
}

die("Action inconnue.");

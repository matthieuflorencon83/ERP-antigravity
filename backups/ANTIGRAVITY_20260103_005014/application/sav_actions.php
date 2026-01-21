<?php
// sav_actions.php - Traitement des formulaires SAV
session_start();
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Accès interdit");
}

$action = $_POST['action'] ?? '';

// --- CREATION DE TICKET ---
if ($action === 'create_ticket') {
    
    // 1. GENERATION NUMERO TICKET (SAV-2025-12-0001)
    $year = date('Y');
    $month = date('m');
    $prefix = "SAV-$year-$month-";
    
    // On cherche le dernier numéro du mois
    $stmt = $pdo->prepare("SELECT numero_ticket FROM sav_tickets WHERE numero_ticket LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute(["$prefix%"]);
    $last = $stmt->fetchColumn();
    
    if ($last) {
        $seq = (int)substr($last, -4);
        $next_seq = str_pad($seq + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $next_seq = '0001';
    }
    $numero_ticket = $prefix . $next_seq;

    // 2. DONNEES
    $client_id = !empty($_POST['client_id']) ? $_POST['client_id'] : null;
    $affaire_id = !empty($_POST['affaire_id']) ? $_POST['affaire_id'] : null;
    
    // Prospect
    $prospect_nom = trim($_POST['prospect_nom'] ?? '');
    $prospect_ville = trim($_POST['prospect_ville'] ?? '');
    $prospect_tel = trim($_POST['prospect_telephone'] ?? '');
    
    // Si pas de client ID et pas de nom prospect, erreur
    if (!$client_id && !$prospect_nom) {
        die("Erreur : Veuillez sélectionner un client ou saisir un nom de prospect.");
    }

    $type_panne = $_POST['type_panne'];
    $description = $_POST['description'];
    $urgence = $_POST['urgence'];
    $decision = $_POST['decision']; // DIAGNOSTIC ou REPARATION
    
    // Statut initial
    $statut = ($decision === 'REPARATION') ? 'A_PLANIFIER' : 'EN_DIAGNOSTIC';
    
    // 3. INSERTION
    $stmt = $pdo->prepare("
        INSERT INTO sav_tickets (
            numero_ticket, client_id, affaire_id, 
            prospect_nom, prospect_ville, prospect_telephone,
            type_panne, description_initiale, statut, urgence, created_by
        ) VALUES (
            ?, ?, ?, 
            ?, ?, ?, 
            ?, ?, ?, ?, ?
        )
    ");
    
    $stmt->execute([
        $numero_ticket, $client_id, $affaire_id,
        $prospect_nom, $prospect_ville, $prospect_tel,
        $type_panne, $description, $statut, $urgence, $_SESSION['user_id']
    ]);
    
    $ticket_id = $pdo->lastInsertId();

    // 4. GESTION DES FICHIERS (Boîte à Preuves)
    if (!empty($_FILES['pj']['name'][0])) {
        // Création dossier : uploads/sav/{numero_ticket}
        $target_dir = "uploads/sav/" . $numero_ticket . "/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $count = count($_FILES['pj']['name']);
        for ($i = 0; $i < $count; $i++) {
            $filename = basename($_FILES['pj']['name'][$i]);
            // Nettoyage nom
            $filename = preg_replace('/[^A-Za-z0-9\-\_\.]/', '', $filename);
            $target_file = $target_dir . $filename;
            
            move_uploaded_file($_FILES['pj']['tmp_name'][$i], $target_file);
        }
    }

    // 5. REDIRECTION
    // Vers le fil d'actualité ou le détail du ticket (que je n'ai pas encore fait, donc le Fil)
    // Idéalement on afficherait une notification de succès.
    // Créeons sav_fil.php minimaliste ou redirigeons vers sav_creation avec message.
    
    
    $_SESSION['flash_message'] = "✅ Ticket SAV créé avec succès : <b>$numero_ticket</b>";
    $_SESSION['flash_type'] = "success";
    
    header("Location: sav_creation.php");
    exit;
}

// --- TRAITEMENT DIAGNOSTIC MOBILE ---
if ($action === 'save_diagnostic') {
    $ticket_id = (int)$_POST['ticket_id'];
    $origine = $_POST['origine_panne'];
    $besoin_piece = (int)$_POST['besoin_piece']; // 1 ou 0
    $note = trim($_POST['note_technique'] ?? '');
    
    // 1. MISE A JOUR TICKET
    // Statut : Si pièce -> PIECE_A_COMMANDER, Sinon -> RESOLU (simplifié)
    $statut_new = ($besoin_piece === 1) ? 'PIECE_A_COMMANDER' : 'RESOLU';
    
    // On met à jour sans écraser la description initiale (on pourrait concaténer la note)
    $stmt = $pdo->prepare("
        UPDATE sav_tickets 
        SET origine_panne = ?, 
            statut = ?, 
            description_initiale = CONCAT(description_initiale, '\n\n[DIAG] ', ?) 
        WHERE id = ?
    ");
    $stmt->execute([$origine, $statut_new, $note, $ticket_id]);
    
    // 2. GESTION PIECE & PHOTO
    if ($besoin_piece === 1) {
        $designation = trim($_POST['designation_piece'] ?? 'Pièce inconnue');
        $photo_path = null;
        
        // Upload Photo
        if (!empty($_FILES['photo_diag']['name'])) {
            // Dossier : uploads/sav_diag/
            $year = date('Y');
            $target_dir = "uploads/sav_diag/$year/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            $filename = time() . "_" . basename($_FILES['photo_diag']['name']);
            $target_file = $target_dir . $filename;
            
            if (move_uploaded_file($_FILES['photo_diag']['tmp_name'], $target_file)) {
                $photo_path = $target_file;
            }
        }
        
        // Insertion Ligne Diag
        $stmtLine = $pdo->prepare("
            INSERT INTO sav_lignes_diagnostic (ticket_id, designation_piece, action_requise, photo_preuve_path, quantite)
            VALUES (?, ?, 'REMPLACEMENT', ?, 1.0)
        ");
        $stmtLine->execute([$ticket_id, $designation, $photo_path]);
    }
    
    // 3. REDIRECTION
    $_SESSION['flash_message'] = "🚀 Diagnostic enregistré ! Statut : " . $statut_new;
    $_SESSION['flash_type'] = "success";
    
    header("Location: sav_fil.php");
    exit;
}

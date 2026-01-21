<?php
// sav_generer_commande.php
session_start();
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

// Vérification CSRF/Auth
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['ticket_id'])) {
    die("Accès interdit");
}

$ticket_id = (int)$_POST['ticket_id'];

// 1. Récupération du Ticket et des lignes à commander
$stmt = $pdo->prepare("
    SELECT t.*, c.nom_principal, c.id as client_id 
    FROM sav_tickets t
    LEFT JOIN clients c ON t.client_id = c.id
    WHERE t.id = ?
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) die("Ticket introuvable");

// Lignes de pièces à commander (action_requise = REMPLACEMENT)
// Et qui n'ont pas déjà été traitées (optionnel, pour l'instant on prend tout)
$stmtLines = $pdo->prepare("
    SELECT * FROM sav_lignes_diagnostic 
    WHERE ticket_id = ? 
    AND action_requise = 'REMPLACEMENT' 
    AND (article_id IS NULL OR article_id > 0)
");
$stmtLines->execute([$ticket_id]);
$lignes = $stmtLines->fetchAll();

if (empty($lignes)) {
    $_SESSION['flash_message'] = "⚠️ Aucune pièce marquée 'A Remplacer' dans ce ticket.";
    $_SESSION['flash_type'] = "warning";
    header("Location: sav_fil.php");
    exit;
}

// 2. Création de la Commande (Brouillon)
// On ne connait pas le fournisseur à priori si les pièces sont génériques. 
// On va créer une commande "Fournisseur Inconnu" ou demander à l'user avant.
// Pour simplifier l'UX "One Click", on va créer une commande SANS fournisseur (si possible) ou un fournisseur par défaut "A DÉFINIR".
// Ou alors, on redirige vers une page de pré-commande.

// OPTION RAPIDE : On crée la commande avec le fournisseur ID 1 (Souvent "Divers" ou le premier) 
// et on met une grosse alerte pour changer le fournisseur.
// Mieux : On check s'il y a un article_id lié, et on prend son fournisseur préféré.
// Sinon -> Fournisseur "A DÉFINIR" (On va dire ID=1 ou NULL si autorisé).
// On va mettre NULL si autorisé, sinon 1.

$fournisseur_id = 1; // Fallback
// Essai de trouver un fournisseur via la première pièce identifiée
foreach ($lignes as $l) {
    if ($l['article_id']) {
        $art = $pdo->query("SELECT fournisseur_prefere_id FROM articles WHERE id = " . $l['article_id'])->fetch();
        if ($art && $art['fournisseur_prefere_id']) {
            $fournisseur_id = $art['fournisseur_prefere_id'];
            break;
        }
    }
}

try {
    $pdo->beginTransaction();

    // Création Entête Commande
    $ref_temp = 'CMD-SAV-' . time();
    $titre_commande = "SAV #" . $ticket['numero_ticket'] . " - " . ($ticket['client_nom'] ?? $ticket['prospect_nom']);
    
    $stmtCmd = $pdo->prepare("
        INSERT INTO commandes_achats 
        (fournisseur_id, affaire_id, date_commande, statut, ref_interne, designation, ticket_id) 
        VALUES (?, ?, NOW(), 'Brouillon', ?, ?, ?)
    ");
    $stmtCmd->execute([
        $fournisseur_id, 
        $ticket['affaire_id'], 
        $ref_temp, 
        $titre_commande, 
        $ticket_id
    ]);
    $commande_id = $pdo->lastInsertId();
    
    // Update Ref Propre
    $ref_clean = "CMD-" . date('Y') . "-" . str_pad($commande_id, 4, '0', STR_PAD_LEFT);
    $pdo->exec("UPDATE commandes_achats SET ref_interne = '$ref_clean' WHERE id = $commande_id");

    // 3. Insertion des Lignes
    $stmtInsertLine = $pdo->prepare("
        INSERT INTO commandes_lignes 
        (commande_id, reference_fournisseur, designation, quantite, prix_unitaire_ht) 
        VALUES (?, ?, ?, ?, 0.00)
    ");

    foreach ($lignes as $l) {
        $ref_art = 'SAV-PIECE';
        $des_art = $l['designation_piece'];
        
        // Si article lié, on récupère plus d'infos
        if ($l['article_id']) {
            $artInfo = $pdo->query("SELECT reference_interne, designation FROM articles WHERE id = " . $l['article_id'])->fetch();
            if ($artInfo) {
                $ref_art = $artInfo['reference_interne'];
                $des_art = $artInfo['designation']; // Ou on garde la designation du diag ?
            }
        }
        
        $stmtInsertLine->execute([
            $commande_id,
            $ref_art,
            $des_art . " (Ticket " . $ticket['numero_ticket'] . ")",
            $l['quantite']
        ]);
    }

    // 4. Update Ticket Status
    $pdo->prepare("UPDATE sav_tickets SET statut = 'PIECE_A_COMMANDER' WHERE id = ?")->execute([$ticket_id]);
    // NOTE : On reste en PIECE_A_COMMANDER tant que la commande n'est pas envoyée ? 
    // Ou on passe en "EN_COURS" ? Disons "EN_COURS" pour marquer que c'est traité.
    $pdo->prepare("UPDATE sav_tickets SET statut = 'EN_COURS' WHERE id = ?")->execute([$ticket_id]);

    $pdo->commit();

    $_SESSION['flash_message'] = "✅ Commande générée avec succès (Brouillon) !";
    $_SESSION['flash_type'] = "success";
    header("Location: commandes_detail.php?id=" . $commande_id);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Erreur lors de la génération de la commande : " . $e->getMessage());
}
?>

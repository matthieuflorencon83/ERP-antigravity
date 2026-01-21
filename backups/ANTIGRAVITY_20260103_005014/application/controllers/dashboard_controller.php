<?php
/**
 * controllers/dashboard_controller.php
 * Logique métier pour le Tableau de Bord
 */

// S'assurer que la connexion DB est présente
if (!isset($pdo)) {
    require_once __DIR__ . '/../db.php';
}

// Initialisation des stats
$stats = [
    'en_attente' => 0,
    'commandees' => 0,
    'arc_recus' => 0,
    'livraisons_prevues' => 0
];

$agenda_metrages = [];
$agenda_poses = [];
$agenda_livraisons = []; // Garder pour compatibilité si besoin

try {
    // 1. LISTES DE COMMANDES POUR LES TUILES
    
    // Commandes en attente (créées mais pas encore commandées)
    $stmt = $pdo->query("
        SELECT ca.id, ca.ref_interne, f.nom as fournisseur_nom, a.nom_affaire, ca.designation
        FROM commandes_achats ca
        JOIN fournisseurs f ON ca.fournisseur_id = f.id
        INNER JOIN affaires a ON ca.affaire_id = a.id
        WHERE ca.date_commande IS NULL
        ORDER BY ca.id DESC
        LIMIT 10
    ");
    $commandes_en_attente = $stmt->fetchAll();
    // Direct COUNT for accurate stats
    $stats['en_attente'] = $pdo->query("
        SELECT COUNT(*) FROM commandes_achats ca 
        INNER JOIN affaires a ON ca.affaire_id = a.id 
        WHERE ca.date_commande IS NULL
    ")->fetchColumn();
    
    // Commandes commandées (date_commande renseignée mais pas ARC)
    $stmt = $pdo->query("
        SELECT ca.id, ca.ref_interne, f.nom as fournisseur_nom, a.nom_affaire, ca.date_commande, ca.designation
        FROM commandes_achats ca
        JOIN fournisseurs f ON ca.fournisseur_id = f.id
        INNER JOIN affaires a ON ca.affaire_id = a.id
        WHERE ca.date_commande IS NOT NULL AND ca.date_arc_recu IS NULL
        ORDER BY ca.date_commande DESC
        LIMIT 10
    ");
    $commandes_commandees = $stmt->fetchAll();
    // Direct COUNT for accurate stats
    $stats['commandees'] = $pdo->query("
        SELECT COUNT(*) FROM commandes_achats ca 
        INNER JOIN affaires a ON ca.affaire_id = a.id 
        WHERE ca.date_commande IS NOT NULL AND ca.date_arc_recu IS NULL
    ")->fetchColumn();
    
    // Commandes ARC reçu (ARC reçu mais pas encore livrées)
    $stmt = $pdo->query("
        SELECT ca.id, ca.ref_interne, f.nom as fournisseur_nom, a.nom_affaire, ca.date_arc_recu, ca.designation
        FROM commandes_achats ca
        JOIN fournisseurs f ON ca.fournisseur_id = f.id
        INNER JOIN affaires a ON ca.affaire_id = a.id
        WHERE ca.date_arc_recu IS NOT NULL AND ca.date_livraison_reelle IS NULL
        ORDER BY ca.date_arc_recu DESC
        LIMIT 10
    ");
    $commandes_arc_recus = $stmt->fetchAll();
    // Direct COUNT for accurate stats
    $stats['arc_recus'] = $pdo->query("
        SELECT COUNT(*) FROM commandes_achats ca 
        INNER JOIN affaires a ON ca.affaire_id = a.id 
        WHERE ca.date_arc_recu IS NOT NULL AND ca.date_livraison_reelle IS NULL
    ")->fetchColumn();
    
    // DEAD CODE REMOVED: commandes_recentes was never used
    
    // Livraisons prévues (date_livraison_prevue renseignée mais pas livrées)
    $stmt = $pdo->query("
        SELECT ca.id, ca.ref_interne, f.nom as fournisseur_nom, a.nom_affaire, ca.date_livraison_prevue, ca.designation,
               DATEDIFF(ca.date_livraison_prevue, CURDATE()) as jours_restants
        FROM commandes_achats ca
        JOIN fournisseurs f ON ca.fournisseur_id = f.id
        INNER JOIN affaires a ON ca.affaire_id = a.id
        WHERE ca.date_livraison_prevue IS NOT NULL AND ca.date_livraison_reelle IS NULL
        ORDER BY ca.date_livraison_prevue ASC
        LIMIT 10
    ");
    $commandes_livraisons = $stmt->fetchAll();
    // Direct COUNT for accurate stats
    $stats['livraisons_prevues'] = $pdo->query("
        SELECT COUNT(*) FROM commandes_achats ca 
        INNER JOIN affaires a ON ca.affaire_id = a.id 
        WHERE ca.date_livraison_prevue IS NOT NULL AND ca.date_livraison_reelle IS NULL
    ")->fetchColumn();
    
    // 2. AGENDA MÉTRAGE (30 prochains jours)
    $stmt = $pdo->query("
        SELECT r.*, a.numero_prodevis, a.nom_affaire, c.nom_principal as client_nom, c.ville as ville_chantier,
               DATEDIFF(r.date_rdv, CURDATE()) as jours_restants
        FROM rendez_vous r
        JOIN affaires a ON r.affaire_id = a.id
        JOIN clients c ON a.client_id = c.id
        WHERE r.date_rdv BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND r.type = 'metrage'
        AND r.statut != 'termine'
        ORDER BY r.date_rdv ASC
        LIMIT 15
    ");
    $agenda_metrages = $stmt->fetchAll();
    
    // 3. AGENDA POSES CHANTIER (30 prochains jours)
    $stmt = $pdo->query("
        SELECT r.*, a.numero_prodevis, a.nom_affaire, c.nom_principal as client_nom,
               DATEDIFF(r.date_rdv, CURDATE()) as jours_avant_debut
        FROM rendez_vous r
        JOIN affaires a ON r.affaire_id = a.id
        JOIN clients c ON a.client_id = c.id
        WHERE r.date_rdv BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND r.type = 'pose'
        AND r.statut IN ('planifie', 'en_cours')
        ORDER BY r.date_rdv ASC
        LIMIT 15
    ");
    $agenda_poses = $stmt->fetchAll();
    
} catch (Exception $e) {
    // Log l'erreur mais ne pas tuer le script, laisser la vue gérer l'affichage vide ou erreur
    error_log("Dashboard Logic Error: " . $e->getMessage());
}

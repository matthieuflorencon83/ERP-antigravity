<?php
// tools/audit_business_logic.php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

$report = [];

function check($section, $test, $success_msg, $fail_msg) {
    global $report;
    if ($test) {
        $report[$section][] = ['status' => 'OK', 'msg' => $success_msg];
    } else {
        $report[$section][] = ['status' => 'FAIL', 'msg' => $fail_msg];
    }
}

// 1. INTEGRITE DATABASE
// ------------------------------------------------
// Vérifier Clients orphelins (sans affaire)
$orphans_clients = $pdo->query("SELECT COUNT(*) FROM clients c LEFT JOIN affaires a ON c.id = a.client_id WHERE a.id IS NULL")->fetchColumn();
check('Data Integrity', true, "$orphans_clients clients sans affaire (Normal pour prospects)", "N/A");

// Vérifier Affaires sans Client
$orphans_affaires = $pdo->query("SELECT COUNT(*) FROM affaires a LEFT JOIN clients c ON a.client_id = c.id WHERE c.id IS NULL")->fetchColumn();
check('Data Integrity', $orphans_affaires == 0, "Toutes les affaires sont liées à un client existant", "ALERT: $orphans_affaires affaires orphelines de client!");

// Vérifier Lignes Métrage sans Intervention
$orphans_lignes = $pdo->query("SELECT COUNT(*) FROM metrage_lignes l LEFT JOIN metrage_interventions m ON l.intervention_id = m.id WHERE m.id IS NULL")->fetchColumn();
check('Data Integrity', $orphans_lignes == 0, "Toutes les lignes de métrage sont liées à une intervention", "ALERT: $orphans_lignes lignes de métrage orphelines!");

// 2. LOGIQUE METIER (BUSINESS LOGIC)
// ------------------------------------------------

// A. Flux complet : Client -> Affaire -> Metrage -> Commande
// On cherche des affaires "Gagnées" (Planifiée, En cours) qui n'ont PAS de commande alors qu'il y a du métrage "TRAITÉ" ?? 
// Non, on cherche l'inverse : Des lignes de métrage "TRAITE" qui ne sont liées à aucune ligne de commande?
// Le lien n'est pas direct en BDD (pas de FK metrage_ligne_id dans lignes_achat), c'est une faiblesse.
check('Logic Gaps', false, "", "CRITICAL: Pas de lien FK entre 'lignes_achat' et 'metrage_lignes'. Traceabilité faible.");

// B. Vérification des Statuts
// Est-ce qu'on a des commandes 'Brouillon' vieilles de plus de 30 jours ?
$old_drafts = $pdo->query("SELECT COUNT(*) FROM commandes_achats WHERE statut='Brouillon' AND date_commande < DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
check('Business Logic', $old_drafts == 0, "Pas de vieux brouillons de commande", "WARNING: $old_drafts commandes 'Brouillon' qui trainent > 30j.");

// C. Stocks
// La table existe-t-elle et est-elle utilisée?
try {
    $stock_count = $pdo->query("SELECT COUNT(*) FROM stocks_mouvements")->fetchColumn();
    check('Features', true, "Module Stock actif ($stock_count mouvements)", "N/A");
} catch(Exception $e) {
    check('Features', false, "", "CRITICAL: Table stocks_mouvements manquante ou inaccessible.");
}

// E. Traceability (NEW)
try {
    $cols = $pdo->query("SHOW COLUMNS FROM lignes_achat LIKE 'metrage_ligne_id'")->fetch();
    check('Traceability', $cols, "Structure FK metrage_ligne_id présente", "CRITICAL: Colonne FK manquante!");
    
    // Check usage
    $linked_count = $pdo->query("SELECT COUNT(*) FROM lignes_achat WHERE metrage_ligne_id IS NOT NULL")->fetchColumn();
    // On accepte 0 si la BDD est vide, mais ici on a des tests, donc on veut > 0
    check('Traceability', $linked_count > 0, "Données tracées détectées ($linked_count lignes liées)", "WARNING: Structure OK mais aucune donnée liée trouvée.");
} catch(Exception $e) {
    check('Traceability', false, "", "ERROR: " . $e->getMessage());
}

// F. UI Assets
$css = @file_get_contents(__DIR__ . '/../assets/css/antigravity.css');
$has_glass = strpos($css, 'backdrop-filter') !== false;
check('UI Standards', $has_glass, "Glassmorphism détecté (CSS 2025)", "WARNING: CSS semble daté.");

// D. Facturation (Scope ProDevis)
// On vérifie qu'on n'a PAS de table factures (puisque géré ailleurs)
try {
    $has_factures = $pdo->query("SHOW TABLES LIKE 'factures'")->fetch();
    if ($has_factures) {
         check('Scope', false, "", "INFO: Table 'factures' détectée (Hors Scope ProDevis ?)");
    } else {
         check('Scope', true, "Module Facturation absent (Conforme ProDevis)", "N/A");
    }
} catch(Exception $e) {}

echo json_encode($report, JSON_PRETTY_PRINT);
